<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Config;

use Http\Discovery\Psr18Client;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Constants\SmartIdBaseUrl;

/**
 * Trusted certificates configuration supports two modes:
 * 1. Auto-separation: certificatePath (mixed certificates)
 * 2. Direct paths: caPath and intPath
 */
final class SmartIdConfig
{
    private ClientInterface $httpClient;

    public function __construct(
        private string $relyingPartyUUID,
        private string $relyingPartyName,
        private string $baseUrl = SmartIdBaseUrl::PROD,
        ?ClientInterface $httpClient = null,
        private ?string $certificatePath = null,
        private ?string $caPath = null,
        private ?string $intPath = null,
        private ?LoggerInterface $logger = null

    ) {
        // Validate parameter combinations
        if ($certificatePath !== null && ($caPath !== null || $intPath !== null)) {
            throw new \InvalidArgumentException(
                'Cannot specify both certificatePath and direct paths (caPath/intPath). Choose one mode.'
            );
        }

        if ($certificatePath === null && ($caPath === null || $intPath === null)) {
            throw new \InvalidArgumentException(
                'Must provide either certificatePath (auto-separation) or both caPath and intPath (direct mode).'
            );
        }
        $this->httpClient = new Psr18Client($httpClient);

        // Validate paths exist
        if ($certificatePath !== null && !is_dir($certificatePath)) {
            throw new \RuntimeException("Certificate folder not found: {$certificatePath}");
        }
        if ($caPath !== null && !is_dir($caPath)) {
            throw new \RuntimeException("CA certificate folder not found: {$caPath}");
        }
        if ($intPath !== null && !is_dir($intPath)) {
            throw new \RuntimeException("Intermediate certificate folder not found: {$intPath}");
        }
    }

    public function getHttpClient(): ClientInterface
    {
        return $this->httpClient;
    }

    public function getCertificatePath(): ?string
    {
        return $this->certificatePath;
    }

    public function getCaPath(): ?string
    {
        return $this->caPath;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getIntPath(): ?string
    {
        return $this->intPath;
    }

    public function getRelyingPartyUUID(): string
    {
        return $this->relyingPartyUUID;
    }

    public function getRelyingPartyName(): string
    {
        return $this->relyingPartyName;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param array{relyingPartyUUID?: string, relyingPartyName?: string, baseUrl?: string, certificatePath?: string, caPath?: string, intPath?: string, httpClient?: ClientInterface, logger?: LoggerInterface} $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            httpClient: $config['httpClient'] ?? null,
            relyingPartyUUID: $config['relyingPartyUUID'] ?? throw new \InvalidArgumentException('relyingPartyUUID is required'),
            relyingPartyName: $config['relyingPartyName'] ?? throw new \InvalidArgumentException('relyingPartyName is required'),
            baseUrl: $config['baseUrl'] ?? SmartIdBaseUrl::PROD,
            certificatePath: $config['certificatePath'] ?? null,
            caPath: $config['caPath'] ?? null,
            intPath: $config['intPath'] ?? null,
            logger: $config['logger'] ?? null
        );
    }
}
