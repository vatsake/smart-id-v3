<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\NotificationSigningRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;

class NotificationSigningRequestBuilderTest extends TestCase
{
    private const TEST_DATA = 'This is a document to be signed';

    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $builder = new NotificationSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Please sign')
            ->build();

        $this->assertInstanceOf(NotificationSigningRequest::class, $request);
        $this->assertNotEmpty($request->signatureProtocol);
        $this->assertEquals(self::TEST_DATA, $request->originalData);
    }

    public function testSuccessfulBuildWithAllParams(): void
    {
        $builder = new NotificationSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_256)
            ->withInteractions('Confirm signature', 'Sign this document')
            ->withNonce('unique12345')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->build();

        $this->assertInstanceOf(NotificationSigningRequest::class, $request);
        $this->assertEquals(self::TEST_DATA, $request->originalData);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new NotificationSigningRequestBuilder();
        $result = $builder->withData(self::TEST_DATA, HashAlgorithm::SHA_512);
        $this->assertSame($builder, $result);
    }

    public function testMissingData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set.");

        $builder = new NotificationSigningRequestBuilder();
        $builder->withInteractions('Sign')->build();
    }

    public function testMissingInteractions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = new NotificationSigningRequestBuilder();
        $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->build();
    }

    public function testAllHashAlgorithms(): void
    {
        foreach (HashAlgorithm::cases() as $hashAlgorithm) {
            $builder = new NotificationSigningRequestBuilder();
            $request = $builder
                ->withData(self::TEST_DATA, $hashAlgorithm)
                ->withInteractions('Sign')
                ->build();

            $this->assertInstanceOf(NotificationSigningRequest::class, $request);
        }
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $builder = new NotificationSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testNonceValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce cannot be longer than 30 characters');

        $builder = new NotificationSigningRequestBuilder();
        $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->withNonce('this_is_a_very_long_nonce_that_exceeds_thirty_characters_limit')
            ->build();
    }

    public function testNonceWithinLimit(): void
    {
        $builder = new NotificationSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->withNonce('valid_nonce_123')
            ->build();

        $this->assertInstanceOf(NotificationSigningRequest::class, $request);
    }

    public function testAllCertificateLevels(): void
    {
        foreach (CertificateLevel::cases() as $certificateLevel) {
            $builder = new NotificationSigningRequestBuilder();
            $request = $builder
                ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
                ->withInteractions('Sign')
                ->withCertificateLevel($certificateLevel)
                ->build();

            $this->assertInstanceOf(NotificationSigningRequest::class, $request);
        }
    }

    public function testSignatureProtocolIsRawDigest(): void
    {
        $builder = new NotificationSigningRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->build();

        // Raw digest signature is used for signing operations
        $this->assertNotEmpty($request->signatureProtocol);
    }

    public function testDifferentDataProducesDifferentDigests(): void
    {
        $data1 = 'First document';
        $data2 = 'Second document';

        $builder1 = new NotificationSigningRequestBuilder();
        $request1 = $builder1
            ->withData($data1, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->build();

        $builder2 = new NotificationSigningRequestBuilder();
        $request2 = $builder2
            ->withData($data2, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->build();

        $this->assertNotEquals($request1->signatureProtocolParameters, $request2->signatureProtocolParameters);
    }

    public function testLongDocumentDataCanBeSigned(): void
    {
        $longData = str_repeat('A', 10000);

        $builder = new NotificationSigningRequestBuilder();
        $request = $builder
            ->withData($longData, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->build();

        $this->assertEquals($longData, $request->originalData);
    }
}
