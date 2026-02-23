<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;

/**
 * Tests specific to SigningRequest functionality
 */
class SigningRequestTest extends TestCase
{
    public function testConstructorCreatesSigningRequest(): void
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
            'requestProperties' => ['shareMdClientIpAddress' => false],
            'interactions' => base64_encode(json_encode([['type' => 'displayTextAndPIN', 'displayText60' => 'Test']])),
            'originalData' => 'original test data',
        ];

        $request = new DeviceLinkSigningRequest($data);

        $this->assertEquals(SignatureProtocol::RAW_DIGEST_SIGNATURE->value, $request->signatureProtocol);
        $this->assertEquals($data['signatureProtocolParameters'], $request->signatureProtocolParameters);
        $this->assertEquals($data['requestProperties'], $request->requestProperties);
        $this->assertEquals($data['interactions'], $request->interactions);
        $this->assertEquals('original test data', $request->originalData);
        $this->assertNull($request->nonce);
        $this->assertNull($request->certificateLevel);
        $this->assertNull($request->initialCallbackUrl);
    }

    public function testBuildWithMinimumRequiredFields(): void
    {
        $request = DeviceLinkSigningRequest::builder()
            ->withData('Hello World', HashAlgorithm::SHA_256)
            ->withInteractions('Sign document')
            ->build();

        $this->assertEquals(SignatureProtocol::RAW_DIGEST_SIGNATURE->value, $request->signatureProtocol);
        $this->assertArrayHasKey('digest', $request->signatureProtocolParameters);
        $this->assertArrayHasKey('signatureAlgorithm', $request->signatureProtocolParameters);
        $this->assertEquals(SignatureAlgorithm::RSASSA_PSS->value, $request->signatureProtocolParameters['signatureAlgorithm']);
        $this->assertArrayHasKey('signatureAlgorithmParameters', $request->signatureProtocolParameters);
        $this->assertEquals(HashAlgorithm::SHA_256->value, $request->signatureProtocolParameters['signatureAlgorithmParameters']['hashAlgorithm']);
        $this->assertEquals('Hello World', $request->originalData);
    }

    public function testWithDataGeneratesCorrectDigest(): void
    {
        $rawData = 'Test signing data';
        $hashAlg = HashAlgorithm::SHA_256;
        $expectedDigest = base64_encode(hash($hashAlg->getName(), $rawData, true));

        $request = DeviceLinkSigningRequest::builder()
            ->withData($rawData, $hashAlg)
            ->withInteractions('Sign')
            ->build();

        $this->assertEquals($expectedDigest, $request->signatureProtocolParameters['digest']);
        $this->assertEquals($rawData, $request->originalData);
    }

    public function testWithDataUsingDifferentHashAlgorithms(): void
    {
        $rawData = 'Test data for hashing';

        foreach ([HashAlgorithm::SHA_256, HashAlgorithm::SHA_384, HashAlgorithm::SHA_512] as $hashAlg) {
            $expectedDigest = base64_encode(hash($hashAlg->getName(), $rawData, true));

            $request = DeviceLinkSigningRequest::builder()
                ->withData($rawData, $hashAlg)
                ->withInteractions('Sign')
                ->build();

            $this->assertEquals($expectedDigest, $request->signatureProtocolParameters['digest']);
            $this->assertEquals($hashAlg->value, $request->signatureProtocolParameters['signatureAlgorithmParameters']['hashAlgorithm']);
        }
    }

    public function testToArrayExcludesOriginalData(): void
    {
        $request = DeviceLinkSigningRequest::builder()
            ->withData('Original data should be excluded', HashAlgorithm::SHA_256)
            ->withInteractions('Sign')
            ->build();

        $array = $request->toArray();

        $this->assertArrayNotHasKey('originalData', $array);
    }
}
