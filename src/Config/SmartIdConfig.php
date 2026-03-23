<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Config;

use Http\Discovery\Psr18Client;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Enums\SmartIdEnv;
use Vatsake\SmartIdV3\Validators\CertificateChainValidator;

class SmartIdConfig
{
    private ClientInterface $httpClient;

    private ?CertificateChainValidator $certificateValidator = null;


    public function __construct(
        private string $relyingPartyUUID,
        private string $relyingPartyName,
        private CacheItemPoolInterface $cache,
        private string $certificatePath,
        private SmartIdEnv $env = SmartIdEnv::PROD,
        ?ClientInterface $httpClient = null,
        private ?LoggerInterface $logger = null
    ) {
        $this->httpClient = new Psr18Client($httpClient);

        if ($certificatePath !== null && !is_dir($certificatePath)) {
            throw new \RuntimeException("Certificate folder not found: {$certificatePath}");
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

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
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
        return $this->env->getBaseUrl();
    }

    public function getScheme(): string
    {
        return $this->env->getScheme();
    }

    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    public function getCertificateChainValidator(): CertificateChainValidator
    {
        if ($this->certificateValidator === null) {
            $this->certificateValidator = new CertificateChainValidator($this);
        }
        return $this->certificateValidator;
    }

    /**
     * @param array{relyingPartyUUID?: string, relyingPartyName?: string, cache?: CacheItemPoolInterface, env?: SmartIdEnv, certificatePath?: string, httpClient?: ClientInterface, logger?: LoggerInterface} $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            httpClient: $config['httpClient'] ?? null,
            relyingPartyUUID: $config['relyingPartyUUID'] ?? throw new \InvalidArgumentException('relyingPartyUUID is required'),
            relyingPartyName: $config['relyingPartyName'] ?? throw new \InvalidArgumentException('relyingPartyName is required'),
            certificatePath: $config['certificatePath'] ?? throw new \InvalidArgumentException('certificatePath is required'),
            cache: $config['cache'] ?? throw new \InvalidArgumentException('cache is required'),
            env: $config['env'] ?? SmartIdEnv::PROD,
            logger: $config['logger'] ?? null
        );
    }
}
