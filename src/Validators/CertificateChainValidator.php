<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators;

use phpseclib3\File\X509;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateChainException;

class CertificateChainValidator
{
    private X509 $x509;
    protected ?LoggerInterface $logger = null;

    public function __construct(
        SmartIdConfig $config
    ) {
        $this->logger = $config->getLogger();
        $this->x509 = new X509();

        $it = new \RecursiveDirectoryIterator($config->getCertificatePath(), \FilesystemIterator::SKIP_DOTS);
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $pem = $this->parseCaCertificate($file->getRealPath());
            $this->x509->loadCA($pem);
        }
    }

    private function parseCaCertificate(string $filePath)
    {
        $contents = file_get_contents($filePath);
        $parsedCert = openssl_x509_parse($contents);

        if ($parsedCert === false) {
            throw new \RuntimeException($filePath . " is not a valid PEM certificate.");
        }

        if (!$this->isCA($parsedCert)) {
            throw new \RuntimeException($filePath . " is not a certificate authority.");
        }

        return $contents;
    }

    private function isCA(array $parsedCert): bool
    {
        $bc = $parsedCert['extensions']['basicConstraints'] ?? null;
        if ($bc === null) {
            return false;
        }
        return stripos($bc, 'CA:TRUE') !== false;
    }

    /**
     * @throws CertificateChainException
     */
    public function validateChain(string $pem): void
    {
        /** @var X509 */
        $this->x509->loadX509($pem);

        if ($this->x509->getCurrentCert() === false) {
            throw new CertificateChainException('N/A');
        }

        $cn = $this->x509->getDN(X509::DN_STRING);
        $chain = $this->x509->getChain();

        if (!$this->x509->validateSignature()) {
            $this->logger?->info('Certificate chain validation failed for subject: ' . $cn);
            throw new CertificateChainException($cn);
        }

        foreach ($chain as $cert) {
            $cn = $cert->getDN(X509::DN_STRING);
            if (!$cert->validateDate()) {
                $this->logger?->info('Certificate chain validation failed: certificate expired for subject: ' . $cn);
                throw new CertificateChainException($cn);
            }
        }

        $this->logger?->debug('Certificate chain validation passed for subject: ' . $cn . ' (full chain validated)');
    }

    /**
     * @throws CertificateChainException if parent certificate is unknown
     */
    public function getIssuerCertificate(string $pem): string
    {
        /** @var X509 */
        $this->x509->loadX509($pem);

        if ($this->x509->getCurrentCert() === false) {
            throw new CertificateChainException('N/A');
        }

        /** @var X509 */
        $parent = $this->x509->getChain()[1] ?? null;
        if ($parent === null) {
            throw new CertificateChainException($this->x509->getDN(X509::DN_STRING));
        }
        return $parent->saveX509($parent->getCurrentCert());
    }
}
