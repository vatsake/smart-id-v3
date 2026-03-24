<?php

namespace Vatsake\SmartIdV3\Tests\Config;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SmartIdEnv;
use Vatsake\SmartIdV3\Validators\CertificateChainValidator;

class SmartIdConfigTest extends TestCase
{
    private CacheItemPoolInterface $mockCache;
    private ClientInterface $mockHttpClient;
    private LoggerInterface $mockLogger;
    private string $certificatePath;

    protected function setUp(): void
    {
        $this->mockCache = $this->createMock(CacheItemPoolInterface::class);
        $this->mockHttpClient = $this->createMock(ClientInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->certificatePath = __DIR__ . '/../../tests/resources/trusted-certificates';
    }

    public function testConstructorWithRequiredParameters(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertInstanceOf(SmartIdConfig::class, $config);
    }

    public function testConstructorWithAllParameters(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::DEMO,
            httpClient: $this->mockHttpClient,
            logger: $this->mockLogger
        );

        $this->assertInstanceOf(SmartIdConfig::class, $config);
    }

    public function testGetRelyingPartyUUID(): void
    {
        $uuid = '00000000-0000-4000-8000-000000000000';
        $config = new SmartIdConfig(
            relyingPartyUUID: $uuid,
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertEquals($uuid, $config->getRelyingPartyUUID());
    }

    public function testGetRelyingPartyName(): void
    {
        $name = 'MY_APP';
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: $name,
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertEquals($name, $config->getRelyingPartyName());
    }

    public function testGetCertificatePath(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertEquals($this->certificatePath, $config->getCertificatePath());
    }

    public function testGetCache(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertSame($this->mockCache, $config->getCache());
    }

    public function testGetHttpClient(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            httpClient: $this->mockHttpClient
        );

        $this->assertInstanceOf(ClientInterface::class, $config->getHttpClient());
    }

    public function testGetLogger(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            logger: $this->mockLogger
        );

        $this->assertSame($this->mockLogger, $config->getLogger());
    }

    public function testGetLoggerReturnsNullWhenNotSet(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertNull($config->getLogger());
    }

    public function testGetBaseUrlWithDemoEnvironment(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::DEMO
        );

        $this->assertEquals('https://sid.demo.sk.ee/smart-id-rp/v3', $config->getBaseUrl());
    }

    public function testGetBaseUrlWithProdEnvironment(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::PROD
        );

        $this->assertEquals('https://rp-api.smart-id.com/v3', $config->getBaseUrl());
    }

    public function testGetBaseUrlDefaultsToProd(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertEquals('https://rp-api.smart-id.com/v3', $config->getBaseUrl());
    }

    public function testGetSchemeWithDemoEnvironment(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::DEMO
        );

        $this->assertEquals('smart-id-demo', $config->getScheme());
    }

    public function testGetSchemeWithProdEnvironment(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::PROD
        );

        $this->assertEquals('smart-id', $config->getScheme());
    }

    public function testGetSchemeDefaultsToProd(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $this->assertEquals('smart-id', $config->getScheme());
    }

    public function testGetCertificateChainValidator(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $validator = $config->getCertificateChainValidator();
        $this->assertInstanceOf(CertificateChainValidator::class, $validator);
    }

    public function testGetCertificateChainValidatorLazyCaching(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $validator1 = $config->getCertificateChainValidator();
        $validator2 = $config->getCertificateChainValidator();

        $this->assertSame($validator1, $validator2);
    }

    public function testInvalidCertificatePath(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificate folder not found');

        new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: '/non/existent/path'
        );
    }

    public function testFromArrayWithRequiredParameters(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
        ]);

        $this->assertInstanceOf(SmartIdConfig::class, $config);
        $this->assertEquals('00000000-0000-4000-8000-000000000000', $config->getRelyingPartyUUID());
        $this->assertEquals('DEMO', $config->getRelyingPartyName());
    }

    public function testFromArrayWithAllParameters(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
            'env' => SmartIdEnv::DEMO,
            'httpClient' => $this->mockHttpClient,
            'logger' => $this->mockLogger,
        ]);

        $this->assertInstanceOf(SmartIdConfig::class, $config);
        $this->assertEquals('https://sid.demo.sk.ee/smart-id-rp/v3', $config->getBaseUrl());
        $this->assertSame($this->mockLogger, $config->getLogger());
    }

    public function testFromArrayMissingRelyingPartyUUID(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('relyingPartyUUID is required');

        SmartIdConfig::fromArray([
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
        ]);
    }

    public function testFromArrayMissingRelyingPartyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('relyingPartyName is required');

        SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
        ]);
    }

    public function testFromArrayMissingCache(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cache is required');

        SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'certificatePath' => $this->certificatePath,
        ]);
    }

    public function testFromArrayMissingCertificatePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('certificatePath is required');

        SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
        ]);
    }

    public function testFromArrayWithDefaultEnvironment(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
        ]);

        $this->assertEquals(SmartIdEnv::PROD->getBaseUrl(), $config->getBaseUrl());
    }

    public function testFromArrayWithDemoEnvironment(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
            'env' => SmartIdEnv::DEMO,
        ]);

        $this->assertEquals(SmartIdEnv::DEMO->getBaseUrl(), $config->getBaseUrl());
    }

    public function testFromArrayWithoutLogger(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
        ]);

        $this->assertNull($config->getLogger());
    }

    public function testFromArrayWithLogger(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
            'logger' => $this->mockLogger,
        ]);

        $this->assertSame($this->mockLogger, $config->getLogger());
    }

    public function testFromArrayWithoutHttpClient(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
        ]);

        $this->assertInstanceOf(ClientInterface::class, $config->getHttpClient());
    }

    public function testConstructorWithNullHttpClient(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            httpClient: null
        );

        $this->assertInstanceOf(ClientInterface::class, $config->getHttpClient());
    }

    public function testAllEnvironmentCombinations(): void
    {
        foreach (SmartIdEnv::cases() as $env) {
            $config = new SmartIdConfig(
                relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
                relyingPartyName: 'DEMO',
                cache: $this->mockCache,
                certificatePath: $this->certificatePath,
                env: $env
            );

            $this->assertNotEmpty($config->getBaseUrl());
            $this->assertNotEmpty($config->getScheme());
        }
    }

    public function testConfigurationImmutability(): void
    {
        $uuid = '00000000-0000-4000-8000-000000000000';
        $name = 'TEST_APP';

        $config = new SmartIdConfig(
            relyingPartyUUID: $uuid,
            relyingPartyName: $name,
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        // Verify values remain consistent
        $this->assertEquals($uuid, $config->getRelyingPartyUUID());
        $this->assertEquals($name, $config->getRelyingPartyName());
        $this->assertEquals($uuid, $config->getRelyingPartyUUID());
        $this->assertEquals($name, $config->getRelyingPartyName());
    }

    public function testFromArrayWithInvalidEnvType(): void
    {
        $this->expectException(\TypeError::class);

        SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
            'env' => 'INVALID_ENV',
        ]);
    }

    public function testFromArrayWithNullCache(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => null,
            'certificatePath' => $this->certificatePath,
        ]);
    }

    public function testFromArrayWithExtraUnexpectedKeys(): void
    {
        $config = SmartIdConfig::fromArray([
            'relyingPartyUUID' => '00000000-0000-4000-8000-000000000000',
            'relyingPartyName' => 'DEMO',
            'cache' => $this->mockCache,
            'certificatePath' => $this->certificatePath,
            'unexpectedKey1' => 'value1',
            'unexpectedKey2' => 'value2',
        ]);

        $this->assertInstanceOf(SmartIdConfig::class, $config);
    }

    public function testMultipleCertificateValidatorAccess(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $validators = [];
        for ($i = 0; $i < 5; $i++) {
            $validators[] = $config->getCertificateChainValidator();
        }

        foreach ($validators as $validator) {
            $this->assertSame($validators[0], $validator);
        }
    }

    public function testCertificatePathNormalization(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $path = $config->getCertificatePath();
        $this->assertTrue(is_dir($path), 'Certificate path should be a valid directory');
    }

    public function testHttpClientNotNull(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath
        );

        $httpClient = $config->getHttpClient();
        $this->assertNotNull($httpClient);
        $this->assertInstanceOf(ClientInterface::class, $httpClient);
    }

    public function testDifferentEnvironmentsHaveDifferentBaseUrls(): void
    {
        $configDemo = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::DEMO
        );

        $configProd = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::PROD
        );

        $this->assertNotEquals($configDemo->getBaseUrl(), $configProd->getBaseUrl());
        $this->assertNotEquals($configDemo->getScheme(), $configProd->getScheme());
    }

    public function testDifferentEnvironmentsHaveDifferentSchemes(): void
    {
        $configDemo = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::DEMO
        );

        $configProd = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            env: SmartIdEnv::PROD
        );

        $this->assertStringContainsString('demo', $configDemo->getScheme());
        $this->assertStringNotContainsString('demo', $configProd->getScheme());
    }

    public function testCacheInstancePreservation(): void
    {
        $mockCache1 = $this->createMock(CacheItemPoolInterface::class);
        $mockCache2 = $this->createMock(CacheItemPoolInterface::class);

        $config1 = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $mockCache1,
            certificatePath: $this->certificatePath
        );

        $config2 = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $mockCache2,
            certificatePath: $this->certificatePath
        );

        $this->assertSame($mockCache1, $config1->getCache());
        $this->assertSame($mockCache2, $config2->getCache());
        $this->assertNotSame($config1->getCache(), $config2->getCache());
    }

    public function testHttpClientInstancePreservation(): void
    {
        $mockClient1 = $this->createMock(ClientInterface::class);
        $mockClient2 = $this->createMock(ClientInterface::class);

        $config1 = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            httpClient: $mockClient1
        );

        $config2 = new SmartIdConfig(
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            cache: $this->mockCache,
            certificatePath: $this->certificatePath,
            httpClient: $mockClient2
        );

        $client1 = $config1->getHttpClient();
        $client2 = $config2->getHttpClient();

        $this->assertInstanceOf(ClientInterface::class, $client1);
        $this->assertInstanceOf(ClientInterface::class, $client2);

        $this->assertNotSame($client1, $client2);
    }
}
