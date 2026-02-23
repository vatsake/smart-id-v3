<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;

/**
 * Tests specific to DeviceLinkCertChoiceRequest functionality
 */
class DeviceLinkCertChoiceRequestTest extends TestCase
{
    public function testConstructorCreatesDeviceLinkCertChoiceRequest(): void
    {
        $data = [
            'requestProperties' => ['shareMdClientIpAddress' => false],
        ];

        $request = new DeviceLinkCertChoiceRequest($data);

        $this->assertEquals(['shareMdClientIpAddress' => false], $request->requestProperties);
        $this->assertNull($request->nonce);
        $this->assertNull($request->certificateLevel);
        $this->assertNull($request->initialCallbackUrl);
    }

    public function testConstructorWithOptionalFields(): void
    {
        $data = [
            'requestProperties' => ['shareMdClientIpAddress' => true],
            'nonce' => 'test-nonce',
            'certificateLevel' => CertificateLevel::QUALIFIED->value,
            'initialCallbackUrl' => 'https://example.com/callback',
        ];

        $request = new DeviceLinkCertChoiceRequest($data);

        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
        $this->assertEquals('test-nonce', $request->nonce);
        $this->assertEquals(CertificateLevel::QUALIFIED->value, $request->certificateLevel);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testBuildWithDefaultValues(): void
    {
        $request = DeviceLinkCertChoiceRequest::builder()->build();

        $this->assertEquals(['shareMdClientIpAddress' => false], $request->requestProperties);
        $this->assertNull($request->nonce);
        $this->assertNull($request->certificateLevel);
        $this->assertNull($request->initialCallbackUrl);
    }

    public function testBuildWithAllOptionalFields(): void
    {
        $request = DeviceLinkCertChoiceRequest::builder()
            ->withNonce('test-nonce-123')
            ->withCertificateLevel(CertificateLevel::QSCD)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->withRequestProperties(true)
            ->build();

        $this->assertEquals('test-nonce-123', $request->nonce);
        $this->assertEquals(CertificateLevel::QSCD->value, $request->certificateLevel);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
    }
}
