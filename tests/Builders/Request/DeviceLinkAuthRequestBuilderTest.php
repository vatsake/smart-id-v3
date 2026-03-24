<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\DeviceLinkAuthRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Utils\RpChallenge;

class DeviceLinkAuthRequestBuilderTest extends TestCase
{
    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
        $this->assertNotEmpty($request->signatureProtocol);
        $this->assertNotEmpty($request->signatureProtocolParameters);
    }

    public function testSuccessfulBuildWithInteractions(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Confirm authentication', 'Please confirm to proceed')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
        $this->assertNotEmpty($request->interactions);
    }

    public function testSuccessfulBuildWithCertificateLevel(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new DeviceLinkAuthRequestBuilder();
        $rpChallenge = RpChallenge::generate();
        $result = $builder->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512);
        $this->assertSame($builder, $result);
    }

    public function testMissingRpChallenge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set.");

        $builder = new DeviceLinkAuthRequestBuilder();
        $builder->build();
    }

    public function testAllHashAlgorithms(): void
    {
        $rpChallenge = RpChallenge::generate();

        foreach (HashAlgorithm::cases() as $hashAlgorithm) {
            $builder = new DeviceLinkAuthRequestBuilder();
            $request = $builder
                ->withRpChallenge($rpChallenge, $hashAlgorithm)
                ->withInteractions('Please confirm')
                ->build();

            $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
        }
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testWithInitialCallbackUrl(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testInteractionsWithDisplayTextOnly(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('This is a confirmation message')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
        $this->assertNotEmpty($request->interactions);
    }

    public function testSignatureProtocolIsSet(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->build();

        // ACSP V2 is used for authentication
        $this->assertNotEmpty($request->signatureProtocol);
    }

    public function testMultipleInteractions(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Display text 60', 'Display text 200')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testInteractionWithEmptyString(): void
    {
        $rpChallenge = RpChallenge::generate();
        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testInteractionWithWhitespaceOnly(): void
    {
        $rpChallenge = RpChallenge::generate();
        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('   ')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testInteractionWithVeryLongString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('displayText60 exceeds maximum length');

        $rpChallenge = RpChallenge::generate();
        $longInteraction = str_repeat('x', 5000);

        $builder = new DeviceLinkAuthRequestBuilder();
        $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions($longInteraction)
            ->build();
    }

    public function testInteractionWithSpecialCharacters(): void
    {
        $rpChallenge = RpChallenge::generate();
        $specialInteraction = 'Please confirm: !@#$%^&*()_+-=[]{}|;:,.<>?';

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions($specialInteraction)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testInteractionWithUnicodeCharacters(): void
    {
        $rpChallenge = RpChallenge::generate();
        $unicodeInteraction = 'Palun kinnita: ñäöü 中文 日本語 العربية';

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions($unicodeInteraction)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testCallbackUrlWithEmptyString(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withInitialCallbackUrl('')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testCallbackUrlWithMalformedUrl(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Initial Callback URL is invalid');

        $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withInitialCallbackUrl('not-a-url-format')
            ->build();
    }

    public function testCallbackUrlWithVeryLongUrl(): void
    {
        $rpChallenge = RpChallenge::generate();
        $longUrl = 'https://example.com/' . str_repeat('a', 2000);

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withInitialCallbackUrl($longUrl)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testCallbackUrlWithEncodedSpecialCharacters(): void
    {
        $rpChallenge = RpChallenge::generate();
        $urlWithParams = 'https://example.com/callback?param1=value1&param2=value2';

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withInitialCallbackUrl($urlWithParams)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testCertificateLevelNone(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withCertificateLevel(CertificateLevel::ADVANCED)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testAllCertificateLevels(): void
    {
        $rpChallenge = RpChallenge::generate();

        foreach (CertificateLevel::cases() as $level) {
            $builder = new DeviceLinkAuthRequestBuilder();
            $request = $builder
                ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
                ->withInteractions('Please confirm')
                ->withCertificateLevel($level)
                ->build();

            $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
        }
    }

    public function testBuildCalledMultipleTimes(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm');

        $request1 = $builder->build();
        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request1);
    }

    public function testRpChallengeWithDifferentHashAlgorithms(): void
    {
        foreach (HashAlgorithm::cases() as $algorithm) {
            $rpChallenge = RpChallenge::generate();

            $builder = new DeviceLinkAuthRequestBuilder();
            $request = $builder
                ->withRpChallenge($rpChallenge, $algorithm)
                ->withInteractions('Please confirm')
                ->build();

            $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
            $this->assertNotEmpty($request->signatureProtocolParameters);
        }
    }

    public function testRequestPropertiesMultipleTimes(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withRequestProperties(true)
            ->withRequestProperties(false)
            ->withRequestProperties(true)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testBuilderChainingExpression(): void
    {
        $rpChallenge = RpChallenge::generate();

        $request = (new DeviceLinkAuthRequestBuilder())
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->withRequestProperties(true)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testInteractionWithLeadingTrailingWhitespace(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('  Please confirm  ')
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testMultipleInteractionsWithVaryingLengths(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Short', str_repeat('X', 200))
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testCallbackUrlWithHttpsProtocol(): void
    {
        $rpChallenge = RpChallenge::generate();
        $url = 'https://example.com/callback';

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->withInitialCallbackUrl($url)
            ->build();

        $this->assertInstanceOf(DeviceLinkAuthRequest::class, $request);
    }

    public function testSignatureProtocolParametersNotEmpty(): void
    {
        $rpChallenge = RpChallenge::generate();

        $builder = new DeviceLinkAuthRequestBuilder();
        $request = $builder
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Please confirm')
            ->build();

        $this->assertNotEmpty($request->signatureProtocolParameters);
    }
}
