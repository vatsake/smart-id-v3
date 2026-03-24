<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\DeviceLinkCertChoiceRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;

class DeviceLinkCertChoiceRequestBuilderTest extends TestCase
{
    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder->build();

        $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
        $this->assertNotEmpty($request->requestProperties);
    }

    public function testSuccessfulBuildWithAllOptionalParams(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder
            ->withNonce('unique12345')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $result = $builder->withNonce('test_nonce');
        $this->assertSame($builder, $result);
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testNonceValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce cannot be longer than 30 characters');

        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $builder
            ->withNonce('this_is_a_very_long_nonce_that_exceeds_thirty_characters_limit')
            ->build();
    }

    public function testNonceWithinLimit(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder
            ->withNonce('valid_nonce_123')
            ->build();

        $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
    }

    public function testAllCertificateLevels(): void
    {
        foreach (CertificateLevel::cases() as $certificateLevel) {
            $builder = new DeviceLinkCertChoiceRequestBuilder();
            $request = $builder
                ->withCertificateLevel($certificateLevel)
                ->build();

            $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
        }
    }

    public function testWithInitialCallbackUrl(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testNoMandatoryParameters(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder->build();

        $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
    }

    public function testRequestPropertiesDefaultValue(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder->build();

        $this->assertFalse($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testEmptyNonceString(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $request = $builder
            ->withNonce('')
            ->build();

        $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
    }

    public function testNonceMaxLength(): void
    {
        $builder = new DeviceLinkCertChoiceRequestBuilder();
        $nonce = 'valid_nonce_12345678901234567';
        $request = $builder
            ->withNonce($nonce)
            ->build();

        $this->assertInstanceOf(DeviceLinkCertChoiceRequest::class, $request);
    }
}
