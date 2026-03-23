<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators\Session;

use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\CertificateList;
use phpseclib3\File\ASN1\Maps\TBSCertList;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Api\Crl\CrlClient;
use Vatsake\SmartIdV3\Api\Ocsp\OcspClient;
use Vatsake\SmartIdV3\ASN1\OcspBasicResponse;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\HttpException;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateChainException;
use Vatsake\SmartIdV3\Exceptions\Validation\CrlRevocationException;
use Vatsake\SmartIdV3\Exceptions\Validation\CrlSignatureException;
use Vatsake\SmartIdV3\Exceptions\Validation\CrlUrlMissingException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspCertificateRevocationException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspKeyUsageException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspResponseTimeException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspSignatureException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspUrlMissingException;
use Vatsake\SmartIdV3\Exceptions\Validation\UnknownSignatureAlgorithmOidException;
use Vatsake\SmartIdV3\Requests\Ocsp\OcspRequest;
use Vatsake\SmartIdV3\Responses\Ocsp\OcspResponse;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Utils\Asn1Helper;
use Vatsake\SmartIdV3\Utils\PemFormatter;
use Vatsake\SmartIdV3\Validators\CertificateChainValidator;
use Vatsake\SmartIdV3\Validators\Session\SessionValidatorInterface;

/**
 * Validates certificate revocation status via OCSP or CRL
 */
class RevocationValidator implements SessionValidatorInterface
{
    private const OCSP_RESPONSE_TIME_SKEW_SECONDS = 300;

    private OcspClient $ocspClient;
    private CrlClient $crlClient;
    private CacheItemPoolInterface $cache;
    private CertificateChainValidator $chainValidator;
    private ?LoggerInterface $logger;

    public function __construct(
        SmartIdConfig $config,
        private SigningSession|AuthSession $session,
    ) {
        $this->ocspClient = new OcspClient($config);
        $this->crlClient = new CrlClient($config);
        $this->chainValidator = $config->getCertificateChainValidator();
        $this->cache = $config->getCache();
        $this->logger = $config->getLogger();
    }

    /**
     * @throws CertificateChainException if certificate chain is invalid
     * @throws OcspCertificateRevocationException if certificate is revoked
     * @throws OcspResponseTimeException if OCSP response time is outside acceptable window
     * @throws OcspSignatureException if OCSP response signature is invalid
     * @throws OcspKeyUsageException if OCSP responder certificate key usage is invalid
     * @throws OcspUrlMissingException if OCSP URL is not found
     * @throws CrlRevocationException if certificate is revoked according to CRL or CRL request fails
     * @throws CrlSignatureException if CRL signature is invalid
     * @throws CrlUrlMissingException if CRL URL is not found
     * @throws UnknownSignatureAlgorithmOidException if signature algorithm OID is unknown
     */
    public function validate(): void
    {
        $this->logger?->debug('SMART-ID certificate revocation validation started.');

        $pem = PemFormatter::addPemHeaders($this->session->certificate->valueInBase64);

        try {
            $this->validateRevocationViaOcsp($pem);
            $this->logger?->debug('SMART-ID certificate revocation validation completed.');
        } catch (OcspUrlMissingException | HttpException $e) {
            $this->logger?->info('OCSP URL is missing or could not be contacted, falling back to CRL validation.');
            $this->validateRevocationViaCrl($pem);
            $this->logger?->debug('SMART-ID certificate revocation validation completed via CRL.');
        }
    }

    private function validateRevocationViaOcsp(string $pem): void
    {
        $parsedCert = openssl_x509_parse($pem);

        // Get OCSP URL from certificate
        $ocspUrl = $this->extractOcspUrl($parsedCert);
        if ($ocspUrl === null) {
            $this->logger?->info('Certificate does not contain OCSP URL.');
            throw new OcspUrlMissingException('Certificate does not contain OCSP URL.');
        }

        // Get issuer certificate
        $issuerCert = $this->chainValidator->getIssuerCertificate($pem);
        $this->checkOcspStatus($ocspUrl, $pem, $issuerCert);
    }

    private function extractOcspUrl(array $parsedCert): ?string
    {
        $authorityInfo = $parsedCert['extensions']['authorityInfoAccess'] ?? null;
        if ($authorityInfo === null) {
            return null;
        }

        if (preg_match('/OCSP\s*-\s*URI:\s*([^\n]+)/i', $authorityInfo, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function checkOcspStatus(string $ocspUrl, string $certificatePem, string $issuerCertPem): void
    {
        $request = OcspRequest::builder()->withSubjectCertificate($certificatePem)
            ->withIssuerCertificate($issuerCertPem)
            ->build();
        $response = $this->ocspClient->sendOcspRequest($request, $ocspUrl);
        $this->validateOcspResponse($response);
    }

    private function validateOcspResponse(OcspResponse $response): void
    {
        $this->logger?->debug('OCSP response received. Validating response...');

        $this->validateCertificateStatus($response);
        $this->validateResponderCertificate($response);
        $this->validateResponseSignature($response);
        $this->validateResponseTime($response);

        $this->logger?->debug('OCSP response validation completed.');
    }

    private function validateResponderCertificate(OcspResponse $response): void
    {
        $responderCertDer = $response->getResponderCertificate();
        $responderCertPem = PemFormatter::addPemHeaders(base64_encode($responderCertDer));

        $parsedCert = openssl_x509_parse($responderCertPem);

        $this->chainValidator->validateChain($responderCertPem);
        $this->validateKeyUsage($parsedCert);
    }

    private function validateResponseSignature(OcspResponse $response): void
    {
        $responderCertDer = $response->getResponderCertificate();
        $responderCertPem = PemFormatter::addPemHeaders(base64_encode($responderCertDer));

        $signedData = Asn1Helper::encode($response->getTbsResponseData(), OcspBasicResponse::MAP['children']['tbsResponseData']);
        $signature = $response->getSignature();
        $alg = $response->getSignatureAlgorithm();

        $pubKey = openssl_pkey_get_public($responderCertPem);
        if ($pubKey === false) {
            $this->logger?->info('Unable to extract public key from OCSP responder certificate.');
            throw new OcspSignatureException('Unable to extract public key from OCSP responder certificate.');
        }

        $result = openssl_verify($signedData, $signature, $pubKey, $alg);
        if ($result !== 1) {
            $this->logger?->info('OCSP response signature verification failed.');
            throw new OcspSignatureException();
        }
        $this->logger?->debug('OCSP response signature validation passed.');
    }

    private function validateResponseTime(OcspResponse $response): void
    {
        $thisUpdate = $response->getThisUpdate();
        $nextUpdate = $response->getNextUpdate();
        $currentTime = time();

        if ($currentTime < $thisUpdate - self::OCSP_RESPONSE_TIME_SKEW_SECONDS || $currentTime > $thisUpdate + self::OCSP_RESPONSE_TIME_SKEW_SECONDS) {
            $this->logger?->info('OCSP response is not current (thisUpdate is too old or in the future).');
            throw new OcspResponseTimeException();
        }

        if ($nextUpdate !== null && $currentTime > $nextUpdate + self::OCSP_RESPONSE_TIME_SKEW_SECONDS) {
            $this->logger?->info('OCSP response is expired (nextUpdate is in the past).');
            throw new OcspResponseTimeException();
        }
        $this->logger?->debug('OCSP response time validation passed.');
    }

    private function validateCertificateStatus(OcspResponse $response): void
    {
        $status = $response->getCertificateStatus();
        if ($status !== 'good') {
            $this->logger?->info("OCSP responder certificate status is '{$status}'.");
            throw new OcspCertificateRevocationException();
        }
        $this->logger?->debug('OCSP responder certificate status validation passed.');
    }

    private function validateKeyUsage(array $parsedCert): void
    {
        $extKeyUsage = $parsedCert['extensions']['extendedKeyUsage'] ?? '';
        if (stripos($extKeyUsage, 'OCSP Signing') === false) {
            $this->logger?->info('OCSP responder certificate does not have OCSP signing extended key usage.');
            throw new OcspKeyUsageException();
        }
        $this->logger?->debug('OCSP responder certificate extended key usage validation passed for subject: ' . ($parsedCert['subject']['CN'] ?? 'N/A'));
    }

    private function validateRevocationViaCrl(string $pem): void
    {
        $parsedCert = openssl_x509_parse($pem);
        $crlUrl = $this->extractCrlUrl($parsedCert);

        $crlItem = $this->cache->getItem('crl.' . md5($crlUrl));

        if ($crlItem->isHit()) {
            $this->logger?->debug('CRL cache hit for URL: ' . $crlUrl);
            $certList = $crlItem->get();
        } else {
            try {
                $crlDer = $this->crlClient->fetchUrl($crlUrl);
            } catch (HttpException $e) {
                $this->logger?->info('Failed to fetch CRL from URL: ' . $crlUrl);
                throw new CrlRevocationException();
            }

            $temp = CertificateList::MAP;
            $temp['children']['tbsCertList'] = ['type' => ASN1::TYPE_ANY];
            $dec = Asn1Helper::decode($crlDer, $temp);
            $certList = Asn1Helper::decode($dec['tbsCertList']->element, TBSCertList::MAP)['revokedCertificates'] ?? [];
            $alg = $this->mapOidToAlgorithm(ASN1::getOID($dec['signatureAlgorithm']['algorithm']));

            $certList = array_map(fn($item) => $item['userCertificate']->toHex(), $certList);
            $crlItem->set($certList)->expiresAfter(3600 * 24);
            $this->cache->save($crlItem);

            $this->validateCrlSignature($pem, $dec['signature'], $dec['tbsCertList']->element, $alg);
        }

        $this->validateCertificateStatusFromCrl($parsedCert, $certList);
    }

    private function extractCrlUrl(array $parsedCert): ?string
    {
        $crlDistributionPoints = $parsedCert['extensions']['crlDistributionPoints'] ?? null;
        if ($crlDistributionPoints === null) {
            $this->logger?->info('Certificate does not contain CRL distribution points.');
            throw new CrlUrlMissingException();
        }

        if (preg_match('/URI:\s*([^\n]+)/i', $crlDistributionPoints, $matches)) {
            return trim($matches[1]);
        }

        $this->logger?->info('Certificate does not contain CRL URL.');
        throw new CrlUrlMissingException();
    }

    private function validateCrlSignature(string $pem, string $signature, string $signedData, string $alg): void
    {
        $issuerCert = $this->chainValidator->getIssuerCertificate($pem);

        $pubKey = openssl_pkey_get_public($issuerCert);
        $signature = substr($signature, 1); // Remove leading byte
        $result = openssl_verify($signedData, $signature, $pubKey, $alg);
        if ($result !== 1) {
            $this->logger?->info('CRL signature verification failed.');
            throw new CrlSignatureException();
        }

        $this->logger?->debug('CRL signature validation passed.');
    }

    private function mapOidToAlgorithm(string $oid): string
    {
        $oidMap = [
            '1.2.840.113549.1.1.5' => 'sha1',
            '1.2.840.113549.1.1.11' => 'sha256',
            '1.2.840.113549.1.1.12' => 'sha384',
            '1.2.840.113549.1.1.13' => 'sha512',
            '1.2.840.10045.4.3.1' => 'sha224', // ECDSA with SHA-224
            '1.2.840.10045.4.3.2' => 'sha256', // ECDSA with SHA-256
            '1.2.840.10045.4.3.3' => 'sha384', // ECDSA with SHA-384
            '1.2.840.10045.4.3.4' => 'sha512', // ECDSA with SHA-512
        ];
        $alg = $oidMap[$oid] ?? null;
        if ($alg === null) {
            $this->logger?->error('Unknown signature algorithm OID: ' . $oid);
            throw new UnknownSignatureAlgorithmOidException($oid);
        }
        return $alg;
    }

    private function validateCertificateStatusFromCrl(array $parsedCert, array $crl): void
    {
        if (sizeof($crl) === 0) {
            return;
        }

        $certSerial = strtolower($parsedCert['serialNumberHex']);
        $revoked = in_array($certSerial, $crl);

        if ($revoked) {
            $this->logger?->info('Certificate is revoked according to CRL.');
            throw new CrlRevocationException();
        }

        $this->logger?->debug('Certificate is not revoked according to CRL.');
    }
}
