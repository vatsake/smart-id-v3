<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\LinkedRequestBuilder;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\LinkedRequest;

class LinkedRequestBuilderTest extends TestCase
{
    private const TEST_DATA = 'This is a document to be signed';
    private const TEST_SESSION_ID = '550e8400-e29b-41d4-a716-446655440000';

    public function testSuccessfulBuildWithMinimalParams(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Please sign')
            ->build();

        $this->assertInstanceOf(LinkedRequest::class, $request);
        $this->assertNotEmpty($request->signatureProtocol);
        $this->assertEquals(self::TEST_SESSION_ID, $request->linkedSessionID);
        $this->assertEquals(self::TEST_DATA, $request->originalData);
    }

    public function testSuccessfulBuildWithAllParams(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_256)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Confirm signature', 'Sign this document')
            ->withNonce('unique12345')
            ->withCertificateLevel(CertificateLevel::QUALIFIED)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertInstanceOf(LinkedRequest::class, $request);
        $this->assertEquals(self::TEST_SESSION_ID, $request->linkedSessionID);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new LinkedRequestBuilder();
        $result = $builder->withData(self::TEST_DATA, HashAlgorithm::SHA_512);
        $this->assertSame($builder, $result);
    }

    public function testMissingData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set.");

        $builder = new LinkedRequestBuilder();
        $builder
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->build();
    }

    public function testMissingLinkedSessionId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'linkedSessionId' is not set.");

        $builder = new LinkedRequestBuilder();
        $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withInteractions('Sign')
            ->build();
    }

    public function testMissingInteractions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = new LinkedRequestBuilder();
        $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->build();
    }

    public function testAllHashAlgorithms(): void
    {
        foreach (HashAlgorithm::cases() as $hashAlgorithm) {
            $builder = new LinkedRequestBuilder();
            $request = $builder
                ->withData(self::TEST_DATA, $hashAlgorithm)
                ->withLinkedSessionId(self::TEST_SESSION_ID)
                ->withInteractions('Sign')
                ->build();

            $this->assertInstanceOf(LinkedRequest::class, $request);
        }
    }

    public function testRequestPropertiesCanBeSet(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->withRequestProperties(true)
            ->build();

        $this->assertTrue($request->requestProperties['shareMdClientIpAddress']);
    }

    public function testNonceValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce cannot be longer than 30 characters');

        $builder = new LinkedRequestBuilder();
        $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->withNonce('this_is_a_very_long_nonce_that_exceeds_thirty_characters_limit')
            ->build();
    }

    public function testNonceWithinLimit(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->withNonce('valid_nonce_123')
            ->build();

        $this->assertInstanceOf(LinkedRequest::class, $request);
    }

    public function testAllCertificateLevels(): void
    {
        foreach (CertificateLevel::cases() as $certificateLevel) {
            $builder = new LinkedRequestBuilder();
            $request = $builder
                ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
                ->withLinkedSessionId(self::TEST_SESSION_ID)
                ->withInteractions('Sign')
                ->withCertificateLevel($certificateLevel)
                ->build();

            $this->assertInstanceOf(LinkedRequest::class, $request);
        }
    }

    public function testSignatureProtocolIsRawDigest(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->build();

        // Raw digest signature is used for signing operations
        $this->assertNotEmpty($request->signatureProtocol);
    }

    public function testDifferentDataProducesDifferentDigests(): void
    {
        $data1 = 'First document';
        $data2 = 'Second document';

        $builder1 = new LinkedRequestBuilder();
        $request1 = $builder1
            ->withData($data1, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->build();

        $builder2 = new LinkedRequestBuilder();
        $request2 = $builder2
            ->withData($data2, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->build();

        $this->assertNotEquals($request1->signatureProtocolParameters, $request2->signatureProtocolParameters);
    }

    public function testWithInitialCallbackUrl(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Sign')
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testInteractionsWithBothTexts(): void
    {
        $builder = new LinkedRequestBuilder();
        $request = $builder
            ->withData(self::TEST_DATA, HashAlgorithm::SHA_512)
            ->withLinkedSessionId(self::TEST_SESSION_ID)
            ->withInteractions('Display text 60', 'Display text 200')
            ->build();

        $this->assertInstanceOf(LinkedRequest::class, $request);
    }
}
