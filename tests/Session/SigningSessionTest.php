<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use Vatsake\SmartIdV3\Builders\Session\SessionValidatorBuilder;
use Vatsake\SmartIdV3\Enums\InteractionType;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Factories\SessionFactory;
use Vatsake\SmartIdV3\Session\SigningSession;

class SigningSessionTest extends BaseSessionTestCase
{
    protected function createSession(
        string $state,
        ?array $result,
        ?string $signatureProtocol,
        ?array $signature,
        ?array $cert,
        ?string $interactionTypeUsed,
        ?string $deviceIp,
        ?array $ignoredProperties
    ): SigningSession {
        $data = [
            'state' => $state,
            'result' => $result,
            'signatureProtocol' => $signatureProtocol,
            'signature' => $signature,
            'cert' => $cert,
            'interactionTypeUsed' => $interactionTypeUsed,
            'deviceIpAddress' => $deviceIp,
            'ignoredProperties' => $ignoredProperties,
        ];

        return SessionFactory::createSigningSession($data, $this->sessionContract, $this->config);
    }

    public function testSessionWithCompleteStateAndOkResult(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'RAW_DIGEST_SIGNATURE',
            signature: [
                'value' => 'c2lnbmF0dXJlVmFsdWU=',
                'flowType' => 'QR',
                'signatureAlgorithm' => 'sha256WithRSAEncryption',
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => 'SHA-256',
                    'maskGenAlgorithm' => [
                        'algorithm' => 'mgf1',
                        'parameters' => [
                            'hashAlgorithm' => 'SHA-256'
                        ]
                    ],
                    'saltLength' => 32,
                    'trailerField' => '0xbc'
                ]
            ],
            cert: [
                'value' => $this->getCertificateValue(),
                'certificateLevel' => 'QUALIFIED'
            ],
            interactionTypeUsed: 'displayTextAndPIN',
            deviceIp: null,
            ignoredProperties: null
        );

        $this->assertSame(SessionState::COMPLETE, $session->state);
        $this->assertTrue($session->isSuccessful());
        $this->assertSame(SessionEndResult::OK, $session->endResult);
        $this->assertSame('PNOEE-40504040001-DEM0-Q', $session->documentNumber);
        $this->assertNotNull($session->certificate);
        $this->assertSame(SignatureProtocol::RAW_DIGEST_SIGNATURE, $session->signatureProtocol);
        $this->assertSame(InteractionType::DISPLAY_TEXT_AND_PIN, $session->interactionTypeUsed);
    }

    public function testValidateReturnsValidatorBuilderForOkResult(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'RAW_DIGEST_SIGNATURE',
            signature: [
                'value' => 'c2lnbmF0dXJlVmFsdWU=',
                'flowType' => 'QR',
                'signatureAlgorithm' => 'sha256WithRSAEncryption',
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => 'SHA-256',
                    'maskGenAlgorithm' => [
                        'algorithm' => 'mgf1',
                        'parameters' => [
                            'hashAlgorithm' => 'SHA-256'
                        ]
                    ],
                    'saltLength' => 32,
                    'trailerField' => '0xbc'
                ]
            ],
            cert: [
                'value' => $this->getCertificateValue(),
                'certificateLevel' => 'QUALIFIED'
            ],
            interactionTypeUsed: 'displayTextAndPIN',
            deviceIp: null,
            ignoredProperties: null
        );

        $validator = $session->validate();
        $this->assertInstanceOf(SessionValidatorBuilder::class, $validator);
    }

    public function testSigningSessionHasRawDigestSignature(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'RAW_DIGEST_SIGNATURE',
            signature: [
                'value' => 'c2lnbmF0dXJlVmFsdWU=',
                'flowType' => 'QR',
                'signatureAlgorithm' => 'sha256WithRSAEncryption',
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => 'SHA-256',
                    'maskGenAlgorithm' => [
                        'algorithm' => 'mgf1',
                        'parameters' => [
                            'hashAlgorithm' => 'SHA-256'
                        ]
                    ],
                    'saltLength' => 32,
                    'trailerField' => '0xbc'
                ]
            ],
            cert: [
                'value' => $this->getCertificateValue(),
                'certificateLevel' => 'QUALIFIED'
            ],
            interactionTypeUsed: 'displayTextAndPIN',
            deviceIp: null,
            ignoredProperties: null
        );

        $this->assertNotNull($session->signature);
        $this->assertSame('c2lnbmF0dXJlVmFsdWU=', $session->signature->value);
    }

    public function testSigningSessionWithoutSignatureData(): void
    {
        $session = $this->createSession(
            state: 'RUNNING',
            result: null,
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->assertNull($session->signature ?? null);
    }
}
