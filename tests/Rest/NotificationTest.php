<?php

namespace Vatsake\SmartIdV3\Tests\Rest;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\InteractionType;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\IncompleteSessionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\SessionTimeoutException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\WrongVcException;
use Vatsake\SmartIdV3\Features\Notification\NotificationSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\CertificateChoiceSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;
use Vatsake\SmartIdV3\Utils\RpChallenge;

class NotificationTest extends TestCase
{
    use MockConfigTrait;

    private const NOTIFICATION_AUTH_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/auth/notification/session-response.json';
    private const NOTIFICATION_SIGNING_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/signing/notification/session-response.json';
    private const NOTIFICATION_CERT_CHOICE_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/signing/notification/cert-choice-session-response.json';

    public function testSuccessfulAuthWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rpChallenge = RpChallenge::generate();
        $req = NotificationAuthRequest::builder()
            ->withInteractions('Hello world')
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $session = $smartId->notification()->authentication()->startEtsi($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());
        $this->assertNotEmpty($session->response->vc);
    }

    public function testSuccessfulAuthWithDocumentRequestAndResponse()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rpChallenge = 'q1miCUX2v3RAQSycBUWdJrRtG6GR1z55c2oIAdjynk3IIquT4CfoSzcdzbLQDL9eTDojGpy+8QKS1jgWzTB58g==';
        $req = NotificationAuthRequest::builder()
            ->withInteractions('Hello world')
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();
        $session = $smartId->notification()->authentication()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');
        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());
        $this->assertNotEmpty($session->response->vc);

        $response = json_decode(file_get_contents(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));

        $authSessionResponse = $smartId->session($session)->getAuthSession();
        $this->assertInstanceOf(AuthSession::class, $authSessionResponse);
        $this->assertEquals(SessionState::COMPLETE, $authSessionResponse->state);
        $this->assertEquals(SessionEndResult::OK, $authSessionResponse->endResult);
        $this->assertEquals(SignatureProtocol::ACSP_V2, $authSessionResponse->signatureProtocol);
        $this->assertEquals('PNOEE-40504040001-DEM0-Q', $authSessionResponse->documentNumber);
        $this->assertTrue($authSessionResponse->isComplete());
        $this->assertTrue($authSessionResponse->isSuccessful());
        $this->assertEquals($req->getInteractions(), $authSessionResponse->getInteractions());
        $this->assertEquals('', $authSessionResponse->getInitialCallbackUrl());
        $this->assertEquals('', $authSessionResponse->getSessionSecret());
        $this->assertEmpty($authSessionResponse->deviceIpAddress);
        $this->assertEquals($rpChallenge, $authSessionResponse->getSignedData());
        $this->assertNotEmpty($authSessionResponse->signature->value);
        $this->assertNotEmpty($authSessionResponse->signature->serverRandom);
        $this->assertNotEmpty($authSessionResponse->signature->userChallenge);
        $this->assertEquals(SignatureAlgorithm::RSASSA_PSS, $authSessionResponse->signature->signatureAlgorithm);
        $this->assertEquals(FlowType::NOTIFICATION, $authSessionResponse->signature->flowType);
        $this->assertEquals(InteractionType::DISPLAY_TEXT_AND_PIN, $authSessionResponse->interactionTypeUsed);
        $this->assertNotEmpty($authSessionResponse->certificate->valueInBase64);
    }

    public function testSuccessfulCertificateChoiceRequestAndResponse()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $req = NotificationCertChoiceRequest::builder()->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $session = $smartId->notification()->signing()->startCertChoice($req, $etsi);
        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $response = json_decode(file_get_contents(self::NOTIFICATION_CERT_CHOICE_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));

        $certChoiceResponse = $smartId->session($session)->getCertChoiceSession();
        $this->assertInstanceOf(CertificateChoiceSession::class, $certChoiceResponse);
        $this->assertEquals(SessionState::COMPLETE, $certChoiceResponse->state);
        $this->assertEquals(SessionEndResult::OK, $certChoiceResponse->endResult);
        $this->assertEquals(FlowType::NOTIFICATION, $certChoiceResponse->signature->flowType);
        $this->assertTrue($certChoiceResponse->isComplete());
        $this->assertTrue($certChoiceResponse->isSuccessful());
        $this->assertNotEmpty($certChoiceResponse->certificate->valueInBase64);
    }

    public function testSuccessfulSigningWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rawData = 'test 1234';
        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $session = $smartId->notification()->signing()->startEtsi($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());
    }

    public function testSuccessfulSigningWithDocumentRequestAndResponse()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId, 'vc' => ['type' => 'numeric4', 'value' => '1234']]));

        $rawData = 'test 1234';
        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $session = $smartId->notification()->signing()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');
        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());
        $this->assertNotEmpty($session->response->vc);

        $response = json_decode(file_get_contents(self::NOTIFICATION_SIGNING_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));

        $signingSessionResponse = $smartId->session($session)->getSigningSession();
        $this->assertInstanceOf(SigningSession::class, $signingSessionResponse);
        $this->assertEquals(SessionState::COMPLETE, $signingSessionResponse->state);
        $this->assertEquals(SessionEndResult::OK, $signingSessionResponse->endResult);
        $this->assertEquals('PNOEE-40504040001-DEM0-Q', $signingSessionResponse->documentNumber);
        $this->assertEquals(SignatureProtocol::RAW_DIGEST_SIGNATURE, $signingSessionResponse->signatureProtocol);
        $this->assertTrue($signingSessionResponse->isComplete());
        $this->assertTrue($signingSessionResponse->isSuccessful());
        $this->assertEquals($req->getInteractions(), $signingSessionResponse->getInteractions());
        $this->assertEquals('', $signingSessionResponse->getInitialCallbackUrl());
        $this->assertEquals('', $signingSessionResponse->getSessionSecret());
        $this->assertEmpty($signingSessionResponse->deviceIpAddress);
        $this->assertEquals($rawData, $signingSessionResponse->getSignedData());
        $this->assertNotEmpty($signingSessionResponse->signature->value);
        $this->assertTrue(!isset($signingSessionResponse->signature->serverRandom));
        $this->assertTrue(!isset($signingSessionResponse->signature->userChallenge));
        $this->assertEquals(SignatureAlgorithm::RSASSA_PSS, $signingSessionResponse->signature->signatureAlgorithm);
        $this->assertEquals(FlowType::NOTIFICATION, $signingSessionResponse->signature->flowType);
        $this->assertEquals(InteractionType::DISPLAY_TEXT_AND_PIN, $signingSessionResponse->interactionTypeUsed);
        $this->assertNotEmpty($signingSessionResponse->certificate->valueInBase64);
    }

    public function testFailedAuthWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rawData = 'test 1234';
        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '30403039972');
        $session = $smartId->notification()->signing()->startEtsi($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $smartId = new SmartId($this->createMockConfig([
            'state' => 'COMPLETE',
            'result' => [
                'endResult' => 'WRONG_VC'
            ]
        ]));

        $authSessionResponse = $smartId->session($session)->getAuthSession();
        $this->assertInstanceOf(AuthSession::class, $authSessionResponse);
        $this->assertTrue($authSessionResponse->isComplete());
        $this->assertEquals(SessionEndResult::WRONG_VC, $authSessionResponse->endResult);
        $this->assertTrue(!isset($authSessionResponse->signature));
        $this->assertTrue(!isset($authSessionResponse->certificate));
        $this->expectException(WrongVcException::class);
        $authSessionResponse->validate();
    }

    public function testIncompleteAuthWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rawData = 'test 1234';
        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '30403039972');
        $session = $smartId->notification()->signing()->startEtsi($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $smartId = new SmartId($this->createMockConfig([
            'state' => 'RUNNING',
        ]));

        $authSessionResponse = $smartId->session($session)->getAuthSession();
        $this->assertInstanceOf(AuthSession::class, $authSessionResponse);
        $this->assertTrue(!isset($authSessionResponse->signature));
        $this->assertTrue(!isset($authSessionResponse->certificate));
        $this->expectException(IncompleteSessionException::class);
        $authSessionResponse->validate();
    }

    public function testFailedSigningWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rawData = 'test 1234';
        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '30403039972');
        $session = $smartId->notification()->signing()->startEtsi($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $smartId = new SmartId($this->createMockConfig([
            'state' => 'COMPLETE',
            'result' => [
                'endResult' => 'WRONG_VC'
            ]
        ]));

        $authSessionResponse = $smartId->session($session)->getSigningSession();
        $this->assertInstanceOf(SigningSession::class, $authSessionResponse);
        $this->expectException(WrongVcException::class);
        $authSessionResponse->validate();
    }

    public function testIncompleteSigningWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rawData = 'test 1234';
        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '30403039972');
        $session = $smartId->notification()->signing()->startEtsi($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $smartId = new SmartId($this->createMockConfig([
            'state' => 'RUNNING',
        ]));

        $signingSessionResponse = $smartId->session($session)->getSigningSession();
        $this->assertInstanceOf(SigningSession::class, $signingSessionResponse);
        $this->expectException(IncompleteSessionException::class);
        $signingSessionResponse->validate();
    }

    public function testFailedCertChoiceWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $req = NotificationCertChoiceRequest::builder()->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $session = $smartId->notification()->signing()->startCertChoice($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $smartId = new SmartId($this->createMockConfig([
            'state' => 'COMPLETE',
            'result' => [
                'endResult' => 'TIMEOUT'
            ]
        ]));

        $authSessionResponse = $smartId->session($session)->getCertChoiceSession();
        $this->assertInstanceOf(CertificateChoiceSession::class, $authSessionResponse);
        $this->expectException(SessionTimeoutException::class);
        $authSessionResponse->validate();
    }

    public function testIncompleteCertChoiceWithEtsiRequest()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $req = NotificationCertChoiceRequest::builder()->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $session = $smartId->notification()->signing()->startCertChoice($req, $etsi);

        $this->assertInstanceOf(NotificationSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());

        $smartId = new SmartId($this->createMockConfig([
            'state' => 'RUNNING',
        ]));

        $certChoiceSessionResponse = $smartId->session($session)->getCertChoiceSession();
        $this->assertInstanceOf(CertificateChoiceSession::class, $certChoiceSessionResponse);
        $this->expectException(IncompleteSessionException::class);
        $certChoiceSessionResponse->validate();
    }
}
