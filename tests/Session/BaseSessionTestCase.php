<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\DocumentUnusableException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ExpectedLinkedSessionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\IncompleteSessionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ProtocolFailureException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\RequiredInteractionNotSupportedByAppException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ServerErrorException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\SessionTimeoutException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\UserRefusedCertChoiceException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\UserRefusedException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\UserRefusedInteractionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\WrongVcException;
use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Session\BaseSession;
use Vatsake\SmartIdV3\Utils\PemFormatter;

abstract class BaseSessionTestCase extends TestCase
{
    protected SmartIdConfig $config;
    protected MockObject|SessionContract $sessionContract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new SmartIdConfig(
            relyingPartyUUID: 'test-uuid',
            relyingPartyName: 'test-name',
            certificatePath: __DIR__ . '/../resources/trusted-mixed-certs'
        );

        $this->sessionContract = $this->createMock(SessionContract::class);
        $this->sessionContract->method('getSessionId')->willReturn('test-session-id');
        $this->sessionContract->method('getSignedData')->willReturn('signed-data');
        $this->sessionContract->method('getInteractions')->willReturn('displayTextAndPIN');
        $this->sessionContract->method('getInitialCallbackUrl')->willReturn('https://callback.url');
    }

    protected function getCertificateValue(): string
    {
        $certFile = __DIR__ . '/../resources/PNOEE-40504040001-DEM0-Q.cer';
        $certContent = file_get_contents($certFile);
        return PemFormatter::stripPemHeaders($certContent);
    }

    abstract protected function createSession(
        string $state,
        ?array $result,
        ?string $signatureProtocol,
        ?array $signature,
        ?array $cert,
        ?string $interactionTypeUsed,
        ?string $deviceIp,
        ?array $ignoredProperties
    ): BaseSession;

    public function testSessionWithRunningState(): void
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

        $this->assertSame(SessionState::RUNNING, $session->state);
        $this->assertFalse($session->isSuccessful());
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
    }

    public function testSessionWithDeviceIp(): void
    {
        $session = $this->createSession(
            state: 'RUNNING',
            result: null,
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: '192.168.1.1',
            ignoredProperties: null
        );

        $this->assertSame('192.168.1.1', $session->deviceIp);
    }

    public function testSessionWithIgnoredProperties(): void
    {
        $ignored = ['property1', 'property2'];
        $session = $this->createSession(
            state: 'RUNNING',
            result: null,
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: $ignored
        );

        $this->assertSame($ignored, $session->ignoredProperties);
    }

    public function testGetSignedData(): void
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

        $this->assertSame('signed-data', $session->getSignedData());
    }

    public function testGetInteractions(): void
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

        $this->assertSame('displayTextAndPIN', $session->getInteractions());
    }

    public function testGetInitialCallbackUrl(): void
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

        $this->assertSame('https://callback.url', $session->getInitialCallbackUrl());
    }

    public function testValidateThrowsIncompleteSessionException(): void
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

        $this->expectException(IncompleteSessionException::class);
        $session->validate();
    }

    public function testValidateThrowsUserRefusedException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::USER_REFUSED->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(UserRefusedException::class);
        $session->validate();
    }

    public function testValidateThrowsSessionTimeoutException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::TIMEOUT->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(SessionTimeoutException::class);
        $session->validate();
    }

    public function testValidateThrowsDocumentUnusableException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::DOCUMENT_UNUSABLE->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(DocumentUnusableException::class);
        $session->validate();
    }

    public function testValidateThrowsWrongVcException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::WRONG_VC->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(WrongVcException::class);
        $session->validate();
    }

    public function testValidateThrowsRequiredInteractionNotSupportedByAppException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(RequiredInteractionNotSupportedByAppException::class);
        $session->validate();
    }

    public function testValidateThrowsUserRefusedCertChoiceException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::USER_REFUSED_CERT_CHOICE->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(UserRefusedCertChoiceException::class);
        $session->validate();
    }

    public function testValidateThrowsUserRefusedInteractionException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::USER_REFUSED_INTERACTION->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(UserRefusedInteractionException::class);
        $session->validate();
    }

    public function testValidateThrowsProtocolFailureException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::PROTOCOL_FAILURE->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(ProtocolFailureException::class);
        $session->validate();
    }

    public function testValidateThrowsExpectedLinkedSessionException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::EXPECTED_LINKED_SESSION->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(ExpectedLinkedSessionException::class);
        $session->validate();
    }

    public function testValidateThrowsServerErrorException(): void
    {
        $session = $this->createSession(
            state: 'COMPLETE',
            result: ['endResult' => SessionEndResult::SERVER_ERROR->value],
            signatureProtocol: null,
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null
        );

        $this->expectException(ServerErrorException::class);
        $session->validate();
    }
}
