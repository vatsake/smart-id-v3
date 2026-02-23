<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;

class NotificationCertChoiceRequestTest extends TestCase
{
    public function testConstructorCreatesNotificationCertChoiceRequest(): void
    {
        $data = [
            'requestProperties' => ['shareMdClientIpAddress' => false],
        ];

        $request = new NotificationCertChoiceRequest($data);

        $this->assertEquals(['shareMdClientIpAddress' => false], $request->requestProperties);
        $this->assertNull($request->nonce);
        $this->assertNull($request->certificateLevel);
    }

    public function testConstructorWithOptionalFields(): void
    {
        $data = [
            'requestProperties' => ['shareMdClientIpAddress' => true],
            'nonce' => 'test-nonce',
            'certificateLevel' => CertificateLevel::QUALIFIED->value,
        ];

        $request = new NotificationCertChoiceRequest($data);

        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
        $this->assertEquals('test-nonce', $request->nonce);
        $this->assertEquals(CertificateLevel::QUALIFIED->value, $request->certificateLevel);
    }

    public function testBuildWithDefaultValues(): void
    {
        $request = NotificationCertChoiceRequest::builder()->build();

        $this->assertEquals(['shareMdClientIpAddress' => false], $request->requestProperties);
        $this->assertNull($request->nonce);
        $this->assertNull($request->certificateLevel);
    }

    public function testBuildWithAllOptionalFields(): void
    {
        $request = NotificationCertChoiceRequest::builder()
            ->withNonce('test-nonce-123')
            ->withCertificateLevel(CertificateLevel::QSCD)
            ->withRequestProperties(true)
            ->build();

        $this->assertEquals('test-nonce-123', $request->nonce);
        $this->assertEquals(CertificateLevel::QSCD->value, $request->certificateLevel);
        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
    }
}
