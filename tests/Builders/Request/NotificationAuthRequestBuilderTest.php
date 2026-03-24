<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\NotificationAuthRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Utils\RpChallenge;

class NotificationAuthRequestBuilderTest extends TestCase
{
    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new NotificationAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->build();

        $this->assertInstanceOf(NotificationAuthRequest::class, $request);
        $this->assertNotEmpty($request->signatureProtocol);
        $this->assertEquals('numeric4', $request->vcType);
    }

    public function testSuccessfulBuildWithAllParams(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new NotificationAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Confirm authentication', 'Please confirm to proceed')
            ->withCertificateLevel(CertificateLevel::ADVANCED)
            ->build();

        $this->assertInstanceOf(NotificationAuthRequest::class, $request);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new NotificationAuthRequestBuilder();
        $rpChallenge = RpChallenge::generate();
        $result = $builder->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512);
        $this->assertSame($builder, $result);
    }

    public function testMissingRpChallenge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set.");

        $builder = new NotificationAuthRequestBuilder();
        $builder->withInteractions('Confirm')->build();
    }

    public function testMissingInteractions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $rpChallenge = RpChallenge::generate();
        $builder = new NotificationAuthRequestBuilder();
        $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();
    }

    public function testAllHashAlgorithms(): void
    {
        $rpChallenge = RpChallenge::generate();

        foreach (HashAlgorithm::cases() as $hashAlgorithm) {
            $builder = new NotificationAuthRequestBuilder();
            $request = $builder
                ->withRpChallenge($rpChallenge, $hashAlgorithm)
                ->withInteractions('Confirm')
                ->build();

            $this->assertInstanceOf(NotificationAuthRequest::class, $request);
        }
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new NotificationAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Confirm')
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testVcTypeIsAlwaysNumeric4(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new NotificationAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Confirm')
            ->build();

        $this->assertEquals('numeric4', $request->vcType);
    }

    public function testAllCertificateLevels(): void
    {
        $rpChallenge = RpChallenge::generate();

        foreach (CertificateLevel::cases() as $certificateLevel) {
            $builder = new NotificationAuthRequestBuilder();
            $request = $builder
                ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
                ->withInteractions('Confirm')
                ->withCertificateLevel($certificateLevel)
                ->build();

            $this->assertInstanceOf(NotificationAuthRequest::class, $request);
        }
    }

    public function testInteractionsWithBothTexts(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new NotificationAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Display text 60', 'Display text 200')
            ->build();

        $this->assertInstanceOf(NotificationAuthRequest::class, $request);
        $this->assertNotEmpty($request->interactions);
    }
}
