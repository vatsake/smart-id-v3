<?php

namespace Vatsake\SmartIdV3\Tests;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Validators\CertificateChainValidator;

trait MockConfigTrait
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&SmartIdConfig */
    protected function createMockConfig(mixed $responseBody = [], int $statusCode = 200, string $contentType = 'application/json'): SmartIdConfig
    {
        $mockHttpClient = $this->createMock(ClientInterface::class);
        $mockStream = $this->createMock(StreamInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockCache = $this->createMock(CacheItemPoolInterface::class);

        if (is_string($responseBody)) {
            $mockStream->method('getContents')->willReturn($responseBody);
        } else {
            $mockStream->method('getContents')->willReturn(json_encode($responseBody));
        }

        $mockCache->method('getItem')->willReturnCallback(function ($key) {
            $mockCacheItem = $this->createMock(\Psr\Cache\CacheItemInterface::class);
            $mockCacheItem->method('isHit')->willReturn(false);
            $mockCacheItem->method('set')->willReturnSelf();
            return $mockCacheItem;
        });

        $mockResponse->method('getBody')->willReturn($mockStream);
        $mockResponse->method('getStatusCode')->willReturn($statusCode);
        $mockResponse->method('getHeaderLine')->willReturn($contentType);

        $mockHttpClient->method('sendRequest')
            ->willReturn($mockResponse);

        /** @var \PHPUnit\Framework\MockObject\MockObject&SmartIdConfig $mockConfig */
        $mockConfig = $this->createMock(SmartIdConfig::class);
        $mockConfig->method('getScheme')->willReturn('smart-id-demo');
        $mockConfig->method('getHttpClient')->willReturn($mockHttpClient);
        $mockConfig->method('getRelyingPartyUUID')->willReturn('00000000-0000-4000-8000-000000000000');
        $mockConfig->method('getRelyingPartyName')->willReturn('DEMO');
        $mockConfig->method('getCertificatePath')->willReturn(__DIR__ . '/resources/trusted-certificates');
        $mockConfig->method('getLogger')->willReturn(null);
        $mockConfig->method('getCertificateChainValidator')->willReturn(new CertificateChainValidator($mockConfig));
        $mockConfig->method('getCache')->willReturn($mockCache);

        return $mockConfig;
    }
}
