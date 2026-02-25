<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators;

use OpenSSLCertificate;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateChainException;
use Vatsake\SmartIdV3\Utils\PemFormatter;

/**
 * This is a base class for certificate validation that:
 * - Organizes certificates into CA (root) and intermediate bundles
 * - Caches bundles based on certificate folder content hash
 * - Provides chain validation logic
 */
abstract class BaseCertificateValidator
{
    private const CA_BUNDLE_FILENAME = 'ca.pem';
    private const INT_BUNDLE_FILENAME = 'int.pem';

    protected string $caBundlePath;
    protected string $intBundlePath;
    protected ?string $bundleDir = null;

    protected ?string $certFolderPath = null;
    protected ?string $caFolderPath = null;
    protected ?string $intFolderPath = null;

    protected ?LoggerInterface $logger = null;

    /**
     * Supports two modes:
     * 1. Auto-separation: new Validator(certFolder: '/path/to/mixed-certs')
     * 2. Direct paths: new Validator(caPath: '/path/to/ca-certs', intPath: '/path/to/int-certs')
     *
     * @throws \InvalidArgumentException if invalid parameter combination is provided
     */
    public function __construct(
        SmartIdConfig $config
    ) {
        $this->logger = $config->getLogger();

        $this->bundleDir = sys_get_temp_dir() . '/smart-id-cert-bundles';
        @mkdir($this->bundleDir, 0700, true);

        // Direct mode
        if ($config->getCaPath() !== null && $config->getIntPath() !== null) {
            $this->caFolderPath = $config->getCaPath();
            $this->intFolderPath = $config->getIntPath();

            $hash = $this->computeFolderHash($this->caFolderPath, $this->intFolderPath);
            $this->initializeBundleCache($hash, function ($caBundlePath, $intBundlePath) {
                $this->buildBundleFromFolder($this->caFolderPath, $caBundlePath);
                $this->buildBundleFromFolder($this->intFolderPath, $intBundlePath);
            });
            return;
        }

        // Auto-separation mode
        $this->certFolderPath = $config->getCertificatePath();
        $hash = $this->computeFolderHash($this->certFolderPath);
        $this->initializeBundleCache($hash, function ($caBundlePath, $intBundlePath) {
            $this->buildAndSaveBundles($caBundlePath, $intBundlePath);
        });
    }

    /**
     * Initialize bundle cache with the given hash, using cache if available
     */
    private function initializeBundleCache(string $hash, callable $buildCallback): void
    {
        $this->clearOldBundles($hash);

        $caBundlePath = $this->bundleDir . '/' . $hash . '/' . self::CA_BUNDLE_FILENAME;
        $intBundlePath = $this->bundleDir . '/' . $hash . '/' . self::INT_BUNDLE_FILENAME;

        if (file_exists($caBundlePath) && file_exists($intBundlePath)) {
            $this->logger?->debug('Using cached certificate bundles with hash: ' . $hash);
            $this->caBundlePath = $caBundlePath;
            $this->intBundlePath = $intBundlePath;
            return;
        }

        @mkdir($this->bundleDir . '/' . $hash, 0700, true);
        $buildCallback($caBundlePath, $intBundlePath);
        $this->logger?->debug('Built certificate bundles with hash: ' . $hash);
        $this->caBundlePath = $caBundlePath;
        $this->intBundlePath = $intBundlePath;
    }

    private function buildAndSaveBundles(string $caBundlePath, string $intBundlePath): void
    {
        $it = new \RecursiveDirectoryIterator($this->certFolderPath, \FilesystemIterator::SKIP_DOTS);
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            [$contents, $certResource, $parsedCert] = $this->parseCaCertificate($file->getRealPath());

            if ($this->isSelfIssued($parsedCert) && $this->isSelfSigned($certResource)) {
                file_put_contents($caBundlePath, $contents . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents($intBundlePath, $contents . PHP_EOL, FILE_APPEND);
            }
        }
    }

    private function parseCaCertificate(string $filePath)
    {
        $contents = file_get_contents($filePath);
        $certResource = openssl_x509_read($contents);
        $parsedCert = openssl_x509_parse($contents);

        if ($parsedCert === false) {
            throw new \RuntimeException($filePath . " is not a valid PEM certificate.");
        }

        if (!$this->isCA($parsedCert)) {
            throw new \RuntimeException($filePath . " is not a certificate authority.");
        }

        return [$contents, $certResource, $parsedCert];
    }

    private function computeFolderHash(string ...$paths): string
    {
        $files = [];

        foreach ($paths as $path) {
            $it = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            foreach ($it as $file) {
                if ($file->isFile()) {
                    $files[] = hash_file('sha256', $file->getRealPath());
                }
            }
        }

        sort($files);
        $hash = hash('sha256', implode('', $files));

        $this->logger?->debug('Computed certificate folder hash: ' . $hash);
        return $hash;
    }

    /**
     * Build a single bundle file from a folder of certificates
     */
    private function buildBundleFromFolder(string $folder, string $bundlePath): void
    {
        $it = new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS);
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            [$contents] = $this->parseCaCertificate($file->getRealPath());
            file_put_contents($bundlePath, $contents . PHP_EOL, FILE_APPEND);
        }
    }

    private function clearOldBundles(string $currentHash): void
    {
        if (!is_dir($this->bundleDir)) {
            return;
        }

        foreach (glob($this->bundleDir . '/*', GLOB_ONLYDIR) as $dir) {
            if (basename($dir) !== $currentHash) {
                $this->deleteDir($dir);
            }
        }
    }

    private function deleteDir(string $dir): void
    {
        foreach (glob($dir . '/*') as $file) {
            is_dir($file) ? $this->deleteDir($file) : @unlink($file);
        }
        @rmdir($dir);
    }

    private function isCA(array $parsedCert): bool
    {
        $bc = $parsedCert['extensions']['basicConstraints'] ?? null;
        if ($bc === null) {
            return false;
        }
        return stripos($bc, 'CA:TRUE') !== false;
    }

    private function getDnString(array $dn): string
    {
        ksort($dn);
        return json_encode($dn, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function isSelfIssued(array $parsedCert): bool
    {
        return $this->getDnString($parsedCert['subject'] ?? []) === $this->getDnString($parsedCert['issuer'] ?? []);
    }

    private function isSelfSigned(OpenSSLCertificate $cert): bool
    {
        $pub = openssl_pkey_get_public($cert);
        if ($pub === false) {
            return false;
        }
        return openssl_x509_verify($cert, $pub) === 1;
    }

    protected function validateChain(OpenSSLCertificate $cert): void
    {
        $result = openssl_x509_checkpurpose(
            $cert,
            X509_PURPOSE_ANY,
            [$this->caBundlePath],
            $this->intBundlePath,
        );
        $parsedCert = openssl_x509_parse($cert);
        $cn = $parsedCert['subject']['CN'] ?? 'N/A';

        if (!$result) {
            $this->logger?->info('Certificate chain validation failed for subject: ' . $cn);
            throw new CertificateChainException();
        }

        $this->logger?->debug('Certificate chain validation passed for subject: ' . $cn);
    }

    /**
     * Load and parse certificate from PEM string
     *
     * @throws \RuntimeException if certificate cannot be parsed
     */
    protected function loadCertificate(string $pem): OpenSSLCertificate
    {
        $pem = PemFormatter::addPemHeaders($pem);
        $certResource = openssl_x509_read($pem);
        if ($certResource === false) {
            throw new \RuntimeException('Unable to parse certificate.');
        }
        return $certResource;
    }
}
