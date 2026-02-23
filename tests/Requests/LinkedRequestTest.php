<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Requests\LinkedRequest;

class LinkedRequestTest extends TestCase
{
    public function testConstructorCreatesLinkedRequest(): void
    {
        $data = [
            'signatureProtocol' => SignatureProtocol::RAW_DIGEST_SIGNATURE->value,
            'signatureProtocolParameters' => [
                'digest' => 'base64digest',
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => HashAlgorithm::SHA_256->value,
                ],
            ],
            'interactions' => base64_encode(json_encode([['type' => 'displayTextAndPIN', 'displayText60' => 'Test']])),
            'requestProperties' => ['shareMdClientIpAddress' => false],
            'linkedSessionId' => 'session-123',
            'originalData' => 'original data',
        ];

        $request = new LinkedRequest($data);

        $this->assertEquals(SignatureProtocol::RAW_DIGEST_SIGNATURE->value, $request->signatureProtocol);
        $this->assertEquals($data['signatureProtocolParameters'], $request->signatureProtocolParameters);
        $this->assertEquals($data['requestProperties'], $request->requestProperties);
        $this->assertEquals('session-123', $request->linkedSessionID);
        $this->assertEquals('original data', $request->originalData);
        $this->assertEquals($data['interactions'], $request->interactions);
        $this->assertNull($request->certificateLevel);
        $this->assertNull($request->initialCallbackUrl);
    }

    public function testConstructorWithOptionalFields(): void
    {
        $data = [
            'signatureProtocol' => SignatureProtocol::RAW_DIGEST_SIGNATURE->value,
            'signatureProtocolParameters' => ['digest' => 'test'],
            'requestProperties' => ['shareMdClientIpAddress' => true],
            'linkedSessionId' => 'session-456',
            'originalData' => 'data',
            'certificateLevel' => CertificateLevel::QUALIFIED->value,
            'interactions' => base64_encode(json_encode([['type' => 'displayTextAndPIN', 'displayText60' => 'Test']])),
            'initialCallbackUrl' => 'https://example.com/callback',
        ];

        $request = new LinkedRequest($data);

        $this->assertEquals(CertificateLevel::QUALIFIED->value, $request->certificateLevel);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testBuildWithMinimumRequiredFields(): void
    {
        $request = LinkedRequest::builder()
            ->withData('Test data', HashAlgorithm::SHA_256)
            ->withLinkedSessionId('session-789')
            ->withInteractions('Sign linked')
            ->build();

        $this->assertEquals(SignatureProtocol::RAW_DIGEST_SIGNATURE->value, $request->signatureProtocol);
        $this->assertArrayHasKey('digest', $request->signatureProtocolParameters);
        $this->assertArrayHasKey('signatureAlgorithm', $request->signatureProtocolParameters);
        $this->assertEquals(SignatureAlgorithm::RSASSA_PSS->value, $request->signatureProtocolParameters['signatureAlgorithm']);
        $this->assertEquals('session-789', $request->linkedSessionID);
        $this->assertEquals('Test data', $request->originalData);
    }

    public function testBuildWithAllOptionalFields(): void
    {
        $request = LinkedRequest::builder()
            ->withData('Test data', HashAlgorithm::SHA_512)
            ->withLinkedSessionId('session-abc')
            ->withInteractions('Display text 60', 'Display text 200')
            ->withNonce('my-nonce-123')
            ->withCertificateLevel(CertificateLevel::ADVANCED)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->withRequestProperties(true)
            ->build();

        $this->assertEquals('my-nonce-123', $request->nonce);
        $this->assertEquals(CertificateLevel::ADVANCED->value, $request->certificateLevel);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
        $this->assertEquals('session-abc', $request->linkedSessionID);
    }

    public function testWithSignatureGeneratesCorrectDigest(): void
    {
        $rawData = 'Test signature data';
        $hashAlg = HashAlgorithm::SHA_256;
        $expectedDigest = base64_encode(hash($hashAlg->getName(), $rawData, true));

        $request = LinkedRequest::builder()
            ->withData($rawData, $hashAlg)
            ->withLinkedSessionId('session-def')
            ->withInteractions('Sign')
            ->build();

        $this->assertEquals($expectedDigest, $request->signatureProtocolParameters['digest']);
        $this->assertEquals($rawData, $request->originalData);
    }

    public function testWithSignatureUsingDifferentHashAlgorithms(): void
    {
        $rawData = 'Test data for hashing';

        foreach ([HashAlgorithm::SHA_256, HashAlgorithm::SHA_384, HashAlgorithm::SHA_512] as $hashAlg) {
            $expectedDigest = base64_encode(hash($hashAlg->getName(), $rawData, true));

            $request = LinkedRequest::builder()
                ->withData($rawData, $hashAlg)
                ->withLinkedSessionId('session-xyz')
                ->withInteractions('Sign')
                ->build();

            $this->assertEquals($expectedDigest, $request->signatureProtocolParameters['digest']);
            $this->assertEquals($hashAlg->value, $request->signatureProtocolParameters['signatureAlgorithmParameters']['hashAlgorithm']);
        }
    }

    public function testToArrayExcludesOriginalData(): void
    {
        $request = LinkedRequest::builder()
            ->withData('Original data should be excluded', HashAlgorithm::SHA_256)
            ->withLinkedSessionId('session-123')
            ->withInteractions('Sign')
            ->build();

        $array = $request->toArray();

        $this->assertArrayNotHasKey('originalData', $array);
    }

    public function testBuildThrowsExceptionWhenSignatureMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set");

        LinkedRequest::builder()
            ->withLinkedSessionId('session-123')
            ->withInteractions('Sign')
            ->build();
    }

    public function testBuildThrowsExceptionWhenLinkedSessionIdMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'linkedSessionId' is not set");

        LinkedRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions('Sign')
            ->build();
    }
}
