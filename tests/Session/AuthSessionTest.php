<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use Vatsake\SmartIdV3\Builders\Session\SessionValidatorBuilder;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Factories\SessionFactory;
use Vatsake\SmartIdV3\Session\AuthSession;

class AuthSessionTest extends BaseSessionTestCase
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
    ): AuthSession {
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

        return SessionFactory::createAuthSession($data, $this->sessionContract, $this->config);
    }

    public function testSessionWithCompleteStateAndOkResult(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'ACSP_V2',
            signature: [
                'value' => 'YWJjZGVmZ2hpams=',
                'serverRandom' => 'c2VydmVyUmFuZG9t',
                'userChallenge' => 'dXNlckNoYWxsZW5nZQ==',
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
        $this->assertSame(SignatureProtocol::ACSP_V2, $session->signatureProtocol);
    }

    public function testValidateReturnsValidatorBuilderForOkResult(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'ACSP_V2',
            signature: [
                'value' => 'YWJjZGVmZ2hpams=',
                'serverRandom' => 'c2VydmVyUmFuZG9t',
                'userChallenge' => 'dXNlckNoYWxsZW5nZQ==',
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

    public function testAuthSessionHasAcspV2Signature(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'ACSP_V2',
            signature: [
                'value' => 'YWJjZGVmZ2hpams=',
                'serverRandom' => 'c2VydmVyUmFuZG9t',
                'userChallenge' => 'dXNlckNoYWxsZW5nZQ==',
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
        $this->assertSame('YWJjZGVmZ2hpams=', $session->signature->value);
        $this->assertSame('c2VydmVyUmFuZG9t', $session->signature->serverRandom);
        $this->assertSame('dXNlckNoYWxsZW5nZQ==', $session->signature->userChallenge);
    }

    public function testAuthSessionWithoutSignatureData(): void
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
