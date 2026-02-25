<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;

/**
 * Tests specific to AuthRequest functionality
 */
class AuthRequestTest extends TestCase
{
    public function testConstructorCreatesAuthRequest(): void
    {
        $data = [
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'signatureProtocolParameters' => [
                'rpChallenge' => 'base64challenge',
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => HashAlgorithm::SHA_256->value,
                ],
            ],
            'requestProperties' => ['shareMdClientIpAddress' => false],
            'interactions' => base64_encode(json_encode([['type' => 'displayTextAndPIN', 'displayText60' => 'Test']])),
        ];

        $request = new NotificationAuthRequest($data);

        $this->assertEquals(SignatureProtocol::ACSP_V2->value, $request->signatureProtocol);
        $this->assertEquals($data['signatureProtocolParameters'], $request->signatureProtocolParameters);
        $this->assertEquals($data['requestProperties'], $request->requestProperties);
        $this->assertEquals($data['interactions'], $request->interactions);
        $this->assertNull($request->certificateLevel);
    }

    public function testConstructorWithOptionalFields(): void
    {
        $data = [
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'signatureProtocolParameters' => ['rpChallenge' => 'test'],
            'requestProperties' => ['shareMdClientIpAddress' => true],
            'interactions' => 'encoded',
            'certificateLevel' => CertificateLevel::QUALIFIED->value,
            'initialCallbackUrl' => 'https://example.com/callback',
        ];

        $request = new NotificationAuthRequest($data);

        $this->assertEquals(CertificateLevel::QUALIFIED->value, $request->certificateLevel);
    }

    public function testBuildWithMinimumRequiredFields(): void
    {
        $rpChallenge = base64_encode(random_bytes(64));

        $request = NotificationAuthRequest::builder()
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
            ->withInteractions('Authenticate')
            ->build();

        $this->assertEquals(SignatureProtocol::ACSP_V2->value, $request->signatureProtocol);
        $this->assertArrayHasKey('rpChallenge', $request->signatureProtocolParameters);
        $this->assertArrayHasKey('signatureAlgorithm', $request->signatureProtocolParameters);
        $this->assertEquals(SignatureAlgorithm::RSASSA_PSS->value, $request->signatureProtocolParameters['signatureAlgorithm']);
        $this->assertArrayHasKey('signatureAlgorithmParameters', $request->signatureProtocolParameters);
        $this->assertEquals(HashAlgorithm::SHA_256->value, $request->signatureProtocolParameters['signatureAlgorithmParameters']['hashAlgorithm']);
    }

    public function testBuildWithAllOptionalFields(): void
    {
        $rpChallenge = base64_encode(random_bytes(64));

        $request = NotificationAuthRequest::builder()
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInteractions('Authenticate', 'Confirmation message')
            ->withCertificateLevel(CertificateLevel::ADVANCED)
            ->withRequestProperties(true)
            ->build();

        $this->assertEquals(CertificateLevel::ADVANCED->value, $request->certificateLevel);
        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
    }

    public function testWithRpChallengeStoresCorrectly(): void
    {
        $rpChallenge = base64_encode(random_bytes(64));

        $request = NotificationAuthRequest::builder()
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
            ->withInteractions('Auth')
            ->build();

        $this->assertEquals($rpChallenge, $request->signatureProtocolParameters['rpChallenge']);
    }

    public function testUsesAcspV2SignatureProtocol(): void
    {
        $rpChallenge = base64_encode(random_bytes(64));

        $request = NotificationAuthRequest::builder()
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_256)
            ->withInteractions('Auth')
            ->build();

        $this->assertEquals(SignatureProtocol::ACSP_V2->value, $request->signatureProtocol);
    }

    public function testBuildThrowsExceptionWhenRpChallengeMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set");

        NotificationAuthRequest::builder()
            ->withInteractions('Auth')
            ->build();
    }
}
