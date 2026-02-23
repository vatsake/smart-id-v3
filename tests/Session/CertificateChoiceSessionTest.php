<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Session\BaseSession;
use Vatsake\SmartIdV3\Session\CertificateChoiceSession;

class CertificateChoiceSessionTest extends BaseSessionTestCase
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
    ): CertificateChoiceSession {
        return new CertificateChoiceSession(
            state: $state,
            session: $this->sessionContract,
            config: $this->config,
            result: $result,
            signatureProtocol: $signatureProtocol,
            signature: $signature,
            cert: $cert,
            interactionTypeUsed: $interactionTypeUsed,
            deviceIp: $deviceIp,
            ignoredProperties: $ignoredProperties
        );
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
                'flowType' => 'QR'
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
        $this->assertSame('PNOEE-40504040001-DEM0-Q', $session->identifier);
        $this->assertNotNull($session->certificate);
        $this->assertSame(SignatureProtocol::ACSP_V2, $session->signatureProtocol);
    }

    public function testCertificateChoiceSessionHasCertificateChoiceSignature(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: [
                'endResult' => SessionEndResult::OK->value,
                'documentNumber' => 'PNOEE-40504040001-DEM0-Q'
            ],
            signatureProtocol: 'ACSP_V2',
            signature: [
                'flowType' => 'QR'
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
        $this->assertSame(FlowType::QR, $session->signature->flowType);
    }

    public function testCertificateChoiceSessionWithoutSignatureData(): void
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
                'flowType' => 'QR'
            ],
            cert: [
                'value' => $this->getCertificateValue(),
                'certificateLevel' => 'QUALIFIED'
            ],
            interactionTypeUsed: 'displayTextAndPIN',
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectNotToPerformAssertions();
        $session->validate();
    }
}
