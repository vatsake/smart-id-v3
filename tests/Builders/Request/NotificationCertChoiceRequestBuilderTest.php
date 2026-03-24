<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\NotificationCertChoiceRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;

class NotificationCertChoiceRequestBuilderTest extends TestCase
{
    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder->build();

        $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
        $this->assertNotEmpty($request->requestProperties);
    }

    public function testSuccessfulBuildWithAllOptionalParams(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder
            ->withNonce('unique12345')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->build();

        $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $result = $builder->withNonce('test_nonce');
        $this->assertSame($builder, $result);
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testNonceValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce cannot be longer than 30 characters');

        $builder = new NotificationCertChoiceRequestBuilder();
        $builder
            ->withNonce('this_is_a_very_long_nonce_that_exceeds_thirty_characters_limit')
            ->build();
    }

    public function testNonceWithinLimit(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder
            ->withNonce('valid_nonce_123')
            ->build();

        $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
    }

    public function testAllCertificateLevels(): void
    {
        foreach (CertificateLevel::cases() as $certificateLevel) {
            $builder = new NotificationCertChoiceRequestBuilder();
            $request = $builder
                ->withCertificateLevel($certificateLevel)
                ->build();

            $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
        }
    }

    public function testNoMandatoryParameters(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder->build();

        $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
    }

    public function testRequestPropertiesDefaultValue(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder->build();

        $this->assertFalse($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testEmptyNonceString(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $request = $builder
            ->withNonce('')
            ->build();

        $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
    }

    public function testNonceMaxLength(): void
    {
        $builder = new NotificationCertChoiceRequestBuilder();
        $nonce = 'valid_nonce_12345678901234567';
        $request = $builder
            ->withNonce($nonce)
            ->build();

        $this->assertInstanceOf(NotificationCertChoiceRequest::class, $request);
    }
}
