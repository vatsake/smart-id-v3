<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators;

use Vatsake\SmartIdV3\ASN1\QcStatements;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateKeyUsageException;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificatePolicyException;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateQcException;
use Vatsake\SmartIdV3\Utils\Asn1Helper;
use Vatsake\SmartIdV3\Utils\PemFormatter;

class SmartIdCertificateValidator extends BaseCertificateValidator
{
    // Smart-ID Policy OIDs
    public const SMART_ID_POLICY_OID_NON_QUALIFIED = '1.3.6.1.4.1.10015.17.1';
    public const SMART_ID_POLICY_OID_QUALIFIED = '1.3.6.1.4.1.10015.17.2';

    // Qualified Certificate OID
    public const QC_COMPLIANCE = '0.4.0.1862.1.1';

    // Extended Key Usage OIDs
    public const EXT_KEY_USAGE_SMART_ID_AUTH = '1.3.6.1.4.1.62306.5.7.0';
    public const EXT_KEY_USAGE_TLS_CLIENT_AUTH = '1.3.6.1.5.5.7.3.2';

    // Cache for parsed certificate
    private ?array $parsedCert = null;

    /**
     * This function validates
     * - certificate chain
     * - certificate validity period
     * - presence of required Smart-ID policy OIDs
     * - key usage and extended key usage for Smart-ID authentication certificates
     *
     * @param string $pem
     * @param CertificateLevel $expectedLevel - expected assurance level (qualified or advanced)
     * @throws CertificateChainException if certificate chain is invalid
     * @throws CertificatePolicyException if required policies are missing
     * @throws CertificateKeyUsageException if key usage or extended key usage is invalid
     */
    public function validateAuthCertificate(string $pem, CertificateLevel $expectedLevel = CertificateLevel::ADVANCED): void
    {
        $this->parsedCert = null;
        $certResource = $this->loadCertificate($pem);
        $pem = PemFormatter::addPemHeaders($pem);
        $cn = $this->getParsedCert($pem)['subject']['CN'] ?? 'N/A';

        $this->logger?->debug('Certificate validation started for subject: ' . $cn);

        $this->validateChain($certResource);
        $this->validateSmartIDPolicyOids($pem, $expectedLevel);
        $this->validateAuthKeyPolicies($pem);

        $this->logger?->debug('Certificate validation successful for subject: ' . $cn);
    }

    /**
     * This function validates
     * - certificate chain
     * - certificate validity period
     * - presence of required Smart-ID policy OIDs
     * - key usage and extended key usage for Smart-ID signing certificates
     * - for qualified certificates, presence of QC Compliance statement
     *
     * @param string $pem
     * @param CertificateLevel $expectedLevel - expected assurance level (qualified or advanced)
     * @throws CertificateChainException if certificate chain is invalid
     * @throws CertificatePolicyException if required policies are missingCertificatePolicyException
     * @throws CertificateQcException if QC statements are invalid
     * @throws CertificateKeyUsageException if key usage or extended key usage is invalid
     */
    public function validateSmartIdSigningCertificate(string $pem, CertificateLevel $expectedLevel): void
    {
        $this->parsedCert = null;
        $certResource = $this->loadCertificate($pem);
        $pem = PemFormatter::addPemHeaders($pem);
        $cn = $this->getParsedCert($pem)['subject']['CN'] ?? 'N/A';

        $this->logger?->debug('Certificate validation started for subject: ' . $cn);

        $this->validateChain($certResource);
        $this->validateSmartIDPolicyOids($pem, $expectedLevel);
        $this->validateQcStatements($pem, $expectedLevel);
        $this->validateSigningKeyPolicies($pem);

        $this->logger?->debug('Certificate validation successful for subject: ' . $cn);
    }

    private function validateSmartIDPolicyOids(string $pemWithHeaders, CertificateLevel $expectedLevel): void
    {
        $parsedCert = $this->getParsedCert($pemWithHeaders);

        $certPolicies = $parsedCert['extensions']['certificatePolicies'] ?? null;
        if ($certPolicies === null) {
            $this->logger?->info('Certificate Policies extension is missing in certificate with subject: ' . ($parsedCert['subject']['CN'] ?? 'N/A'));
            throw new CertificatePolicyException();
        }

        preg_match_all('/Policy:\s*([0-9.]+)/i', $certPolicies, $matches);
        $foundOids = $matches[1] ?? [];

        $expectedOid = $expectedLevel === CertificateLevel::QUALIFIED
            ? self::SMART_ID_POLICY_OID_QUALIFIED
            : self::SMART_ID_POLICY_OID_NON_QUALIFIED;

        if (!in_array($expectedOid, $foundOids)) {
            $this->logger?->info(sprintf(
                'Certificate does not contain required Smart-ID policy OID %s for %s level',
                $expectedOid,
                $expectedLevel->name
            ));
            throw new CertificatePolicyException();
        }
        $this->logger?->debug('Smart-ID policy OIDs validation passed for subject: ' . ($parsedCert['subject']['CN'] ?? 'N/A'));
    }

    private function validateSigningKeyPolicies(string $pemWithHeaders): void
    {
        $parsed = $this->getParsedCert($pemWithHeaders);
        $keyUsage = $parsed['extensions']['keyUsage'] ?? '';

        if (stripos($keyUsage, 'Non Repudiation') === false) {
            $this->logger?->info('Certificate does not have nonRepudiation key usage required for signing. Key Usage: ' . $keyUsage);
            throw new CertificateKeyUsageException();
        }
        $this->logger?->debug('Key usage validation passed for subject: ' . ($parsed['subject']['CN'] ?? 'N/A'));
    }

    private function validateAuthKeyPolicies(string $pemWithHeaders): void
    {
        $parsed = $this->getParsedCert($pemWithHeaders);
        $keyUsage = $parsed['extensions']['keyUsage'] ?? '';
        $extKeyUsage = $parsed['extensions']['extendedKeyUsage'] ?? '';

        $hasDigitalSignature = stripos($keyUsage, 'Digital Signature') !== false;
        $hasSmartIdAuth = stripos($extKeyUsage, self::EXT_KEY_USAGE_SMART_ID_AUTH) !== false;
        $hasClientAuth = stripos($extKeyUsage, 'TLS Web Client Authentication') !== false
            || stripos($extKeyUsage, self::EXT_KEY_USAGE_TLS_CLIENT_AUTH) !== false;

        // New format (April 2025+)
        if ($hasDigitalSignature && $hasSmartIdAuth) {
            $this->logger?->debug('Key usage and extended key usage validation passed for subject: ' . ($parsed['subject']['CN'] ?? 'N/A'));
            return;
        }

        // Old format (pre-April 2025)
        $hasKeyEncipherment = stripos($keyUsage, 'Key Encipherment') !== false;
        $hasDataEncipherment = stripos($keyUsage, 'Data Encipherment') !== false;

        if ($hasDigitalSignature && $hasKeyEncipherment && $hasDataEncipherment && $hasClientAuth) {
            $this->logger?->debug('Key usage and extended key usage validation passed for subject (pre-April 2025): ' . ($parsed['subject']['CN'] ?? 'N/A'));
            return;
        }

        $this->logger?->info('Certificate does not meet Smart-ID authentication requirements. Key Usage: ' . $keyUsage . ', Extended Key Usage: ' . $extKeyUsage);
        throw new CertificateKeyUsageException();
    }

    private function validateQcStatements(string $pemWithHeaders, CertificateLevel $expectedLevel): void
    {
        // For advanced level, we don't require the QC Compliance statement
        if ($expectedLevel === CertificateLevel::ADVANCED) {
            $this->logger?->debug('QC Statements validation skipped for advanced level certificate.');
            return;
        }

        $parsedCert = $this->getParsedCert($pemWithHeaders);

        $assuranceLevel = $parsedCert['extensions']['qcStatements'] ?? null;
        if ($assuranceLevel === null) {
            $this->logger?->info('Certificate does not contain QC Statements extension required for qualified certificates.');
            throw new CertificateQcException();
        }

        $qcStatements = Asn1Helper::decode($assuranceLevel, QcStatements::MAP);

        foreach ($qcStatements as $statement) {
            if ($statement['statementId'] === self::QC_COMPLIANCE) {
                $this->logger?->debug('QC Statements validation passed for subject: ' . ($parsedCert['subject']['CN'] ?? 'N/A'));
                return;
            }
        }

        $this->logger?->info('Certificate does not meet qualified level requirements (missing QC Compliance statement).');
        throw new CertificateQcException();
    }

    private function getParsedCert(string $pem): array
    {
        if ($this->parsedCert === null) {
            $this->parsedCert = openssl_x509_parse($pem);
        }
        return $this->parsedCert;
    }
}
