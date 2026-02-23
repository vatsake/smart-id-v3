<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators;

use Vatsake\SmartIdV3\Api\Ocsp\OcspClient;
use Vatsake\SmartIdV3\ASN1\OcspBasicResponse;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\OcspException;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateChainException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspCertificateRevocationException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspKeyUsageException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspResponseTimeException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspSignatureException;
use Vatsake\SmartIdV3\Requests\Ocsp\OcspRequest;
use Vatsake\SmartIdV3\Responses\Ocsp\OcspResponse;
use Vatsake\SmartIdV3\Utils\Asn1Helper;
use Vatsake\SmartIdV3\Utils\PemFormatter;

/**
 * Validates certificate revocation status via OCSP
 */
class RevocationValidator extends BaseCertificateValidator
{
    private const OCSP_RESPONSE_TIME_SKEW_SECONDS = 300;

    private OcspClient $ocspClient;

    public function __construct(
        SmartIdConfig $config,
    ) {
        parent::__construct($config);
        $this->ocspClient = new OcspClient($config);
    }

    public function validateCertificateRevocation(string $pem): void
    {
        $certResource = $this->loadCertificate($pem);
        $parsedCert = openssl_x509_parse($certResource);

        // Get OCSP URL from certificate
        $ocspUrl = $this->extractOcspUrl($parsedCert);
        if ($ocspUrl === null) {
            $this->logger?->info('Certificate does not contain OCSP URL.');
            throw new OcspException('Certificate does not contain OCSP URL.');
        }

        // Get issuer certificate
        $issuerCert = $this->getIssuerCertificate($parsedCert);
        if ($issuerCert === null) {
            throw new CertificateChainException();
        }
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

    private function getIssuerCertificate(array $parsedCert): ?string
    {
        $issuerDn = $parsedCert['issuer'] ?? null;
        if ($issuerDn === null) {
            return null;
        }

        $certs = file_get_contents($this->intBundlePath);
        $pattern = '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s';
        if (preg_match_all($pattern, $certs, $matches)) {
            foreach ($matches[0] as $cert) {
                $parsed = openssl_x509_parse($cert);
                if ($parsed === false) {
                    continue;
                }

                $subjDn = json_encode($parsed['subject'] ?? [], JSON_UNESCAPED_UNICODE);
                $issuerDnJson = json_encode($issuerDn, JSON_UNESCAPED_UNICODE);

                if ($subjDn === $issuerDnJson) {
                    return $cert;
                }
            }
        }

        $this->logger?->info('Issuer certificate not found in intermediate bundle for issuer DN: ' . json_encode($issuerDn));

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

        $certResource = $this->loadCertificate($responderCertPem);
        $parsedCert = openssl_x509_parse($certResource);

        $this->validateChain($certResource);
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

    private function validateKeyUsage(array $parsedCert)
    {
        $extKeyUsage = $parsedCert['extensions']['extendedKeyUsage'] ?? null;
        if ($extKeyUsage !== 'OCSP Signing') {
            $this->logger?->info('OCSP responder certificate does not have OCSP signing extended key usage.');
            throw new OcspKeyUsageException();
        }
        $this->logger?->debug('OCSP responder certificate extended key usage validation passed for subject: ' . ($parsedCert['subject']['CN'] ?? 'N/A'));
    }
}
