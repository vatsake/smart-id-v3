<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\DeviceLinkSigningRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;

class DeviceLinkSigningRequestBuilderTest extends TestCase
{
    private const TEST_DATA = 'This is a document to be signed';

    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->build();

        $this->assertInstanceOf(DeviceLinkSigningRequest::class, $request);
        $this->assertNotEmpty($request->signatureProtocol);
        $this->assertEquals(self::TEST_DATA, $request->originalData);
    }

    public function testSuccessfulBuildWithAllParams(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_256)
            ->withInteractions('Confirm signature', 'Sign this document')
            ->withNonce('unique12345')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertInstanceOf(DeviceLinkSigningRequest::class, $request);
        $this->assertEquals(self::TEST_DATA, $request->originalData);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $result = $builder->withData(self::TEST_DATA, HashAlgorithm::SHA_512);
        $this->assertSame($builder, $result);
    }

    public function testMissingData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set.");

        $builder = new DeviceLinkSigningRequestBuilder();
        $builder->build();
    }

    public function testAllHashAlgorithms(): void
    {
        foreach (HashAlgorithm::cases() as $hashAlgorithm) {
            $builder = new DeviceLinkSigningRequestBuilder();
            $request = $builder
                ->withData(self::TEST_DATA, $hashAlgorithm)
                ->withInteractions('Please sign')
                ->build();

            $this->assertInstanceOf(DeviceLinkSigningRequest::class, $request);
        }
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testNonceValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce cannot be longer than 30 characters');

        $builder = new DeviceLinkSigningRequestBuilder();
        $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->withNonce('this_is_a_very_long_nonce_that_exceeds_thirty_characters_limit')
            ->build();
    }

    public function testNonceWithinLimit(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->withNonce('valid_nonce_123')
            ->build();

        $this->assertInstanceOf(DeviceLinkSigningRequest::class, $request);
    }

    public function testAllCertificateLevels(): void
    {
        foreach (CertificateLevel::cases() as $certificateLevel) {
            $builder = new DeviceLinkSigningRequestBuilder();
            $request = $builder
                ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
                ->withInteractions('Please sign')
                ->withCertificateLevel($certificateLevel)
                ->build();

            $this->assertInstanceOf(DeviceLinkSigningRequest::class, $request);
        }
    }

    public function testSignatureProtocolIsRawDigest(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->build();

        $this->assertNotEmpty($request->signatureProtocol);
    }

    public function testDifferentDataProducesDifferentDigests(): void
    {
        $data1 = 'First document';
        $data2 = 'Second document';

        $builder1 = new DeviceLinkSigningRequestBuilder();
        $request1 = $builder1
            ->withData($data1, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->build();

        $builder2 = new DeviceLinkSigningRequestBuilder();
        $request2 = $builder2
            ->withData($data2, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->build();

        $this->assertNotEquals($request1->signatureProtocolParameters, $request2->signatureProtocolParameters);
    }

    public function testWithInitialCallbackUrl(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testInteractionsWithBothTexts(): void
    {
        $builder = new DeviceLinkSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Display text 60', 'Display text 200')
            ->build();

        $this->assertInstanceOf(DeviceLinkSigningRequest::class, $request);
    }
}
