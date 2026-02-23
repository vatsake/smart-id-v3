<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Config;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Constants\SmartIdBaseUrl;

class ConfigTest extends TestCase
{
    private string $testCertPath = __DIR__ . '/../resources/trusted-mixed-certs';
    private string $testCaPath = __DIR__ . '/../resources/trusted-ca-certs';
    private string $testIntPath = __DIR__ . '/../resources/trusted-int-certs';

    public function testConstructorWithCertificatePathMode(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: $this->testCertPath
        );

        $this->assertSame('test-uuid', $config->getRelyingPartyUUID());
        $this->assertSame('test-name', $config->getRelyingPartyName());
        $this->assertSame(SmartIdBaseUrl::PROD, $config->getBaseUrl());
        $this->assertSame($this->testCertPath, $config->getCertificatePath());
        $this->assertNull($config->getCaPath());
        $this->assertNull($config->getIntPath());
        $this->assertInstanceOf(ClientInterface::class, $config->getHttpClient());
    }

    public function testConstructorWithDirectPathsMode(): void
    {
        $config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            caPath: $this->testCaPath,
            intPath: $this->testIntPath
        );

        $this->assertSame('test-uuid', $config->getRelyingPartyUUID());
        $this->assertSame('test-name', $config->getRelyingPartyName());
        $this->assertNull($config->getCertificatePath());
        $this->assertSame($this->testCaPath, $config->getCaPath());
        $this->assertSame($this->testIntPath, $config->getIntPath());
    }

    public function testConstructorWithCustomHttpClient(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);

        $config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            httpClient: $mockClient,
            certificatePath: $this->testCertPath
        );

        $this->assertInstanceOf(ClientInterface::class, $config->getHttpClient());
    }

    public function testConstructorWithLogger(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);

        $config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: $this->testCertPath,
            logger: $mockLogger
        );

        $this->assertSame($mockLogger, $config->getLogger());
    }

    public function testConstructorThrowsExceptionWhenBothModesProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot specify both certificatePath and direct paths');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: $this->testCertPath,
            caPath: $this->testCaPath,
            intPath: $this->testIntPath
        );
    }

    public function testConstructorThrowsExceptionWhenCertificatePathAndCaPathProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot specify both certificatePath and direct paths');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: $this->testCertPath,
            caPath: $this->testCaPath
        );
    }

    public function testConstructorThrowsExceptionWhenCertificatePathAndIntPathProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot specify both certificatePath and direct paths');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: $this->testCertPath,
            intPath: $this->testIntPath
        );
    }

    public function testConstructorThrowsExceptionWhenNoPathsProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide either certificatePath');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name'
        );
    }

    public function testConstructorThrowsExceptionWhenOnlyCaPathProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide either certificatePath');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            caPath: $this->testCaPath
        );
    }

    public function testConstructorThrowsExceptionWhenOnlyIntPathProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide either certificatePath');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            intPath: $this->testIntPath
        );
    }

    public function testConstructorThrowsExceptionWhenCertificatePathNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Certificate folder not found');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: '/nonexistent/path'
        );
    }

    public function testConstructorThrowsExceptionWhenCaPathNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CA certificate folder not found');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            caPath: '/nonexistent/ca',
            intPath: $this->testIntPath
        );
    }

    public function testConstructorThrowsExceptionWhenIntPathNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Intermediate certificate folder not found');

        new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            caPath: $this->testCaPath,
            intPath: '/nonexistent/int'
        );
    }

    public function testFromArrayWithAllParameters(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $config = SmartIdConfig::fromArray([
            'httpClient' => $mockClient,
            'relyingPartyUUID' => 'test-uuid',
            'relyingPartyName' => 'test-name',
            'baseUrl' => SmartIdBaseUrl::DEMO,
            'certificatePath' => $this->testCertPath,
            'logger' => $mockLogger
        ]);

        $this->assertSame('test-uuid', $config->getRelyingPartyUUID());
        $this->assertSame('test-name', $config->getRelyingPartyName());
        $this->assertSame(SmartIdBaseUrl::DEMO, $config->getBaseUrl());
        $this->assertSame($this->testCertPath, $config->getCertificatePath());
        $this->assertSame($mockLogger, $config->getLogger());
    }

    public function testFromArrayWithMinimalParameters(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);

        $config = SmartIdConfig::fromArray([
            'httpClient' => $mockClient,
            'relyingPartyUUID' => 'test-uuid',
            'relyingPartyName' => 'test-name',
            'certificatePath' => $this->testCertPath
        ]);

        $this->assertSame('test-uuid', $config->getRelyingPartyUUID());
        $this->assertSame('test-name', $config->getRelyingPartyName());
        $this->assertSame(SmartIdBaseUrl::PROD, $config->getBaseUrl());
        $this->assertNull($config->getLogger());
    }

    public function testFromArrayThrowsExceptionWhenRelyingPartyUUIDMissing(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('relyingPartyUUID is required');

        SmartIdConfig::fromArray([
            'httpClient' => $mockClient,
            'relyingPartyName' => 'test-name',
            'certificatePath' => $this->testCertPath
        ]);
    }

    public function testFromArrayThrowsExceptionWhenRelyingPartyNameMissing(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('relyingPartyName is required');

        SmartIdConfig::fromArray([
            'httpClient' => $mockClient,
            'relyingPartyUUID' => 'test-uuid',
            'certificatePath' => $this->testCertPath
        ]);
    }
}
