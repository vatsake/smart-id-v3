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
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;
use Vatsake\SmartIdV3\Utils\RpChallenge;

class DeviceLinkTest extends TestCase
{
    use MockConfigTrait;

    private const DEVICE_LINK_AUTH_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/auth/device-link/session-response.json';

    public function testSuccessfulAuthWithEtsiRequestAndResponse()
    {
        $sessionId = '1234567890';
        $sessionSecret = '55555';
        $sessionToken = '9999999';
        $deviceLinkBase = 'https://foobar.com/device-link';
        $smartId = new SmartId($this->createMockConfig([
            'sessionID' => $sessionId,
            'sessionSecret' => $sessionSecret,
            'sessionToken' => $sessionToken,
            'deviceLinkBase' => $deviceLinkBase
        ]));

        $rpChallenge = RpChallenge::generate();
        $req = DeviceLinkAuthRequest::builder()
            ->withInteractions('Hello world')
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->withInitialCallbackUrl('https://localhost/callback.php')
            ->build();
        $etsi = SemanticsIdentifier::fromPersonalNumber('EE', '40404040009');
        $session = $smartId->deviceLink()->authentication()->startEtsi($req, $etsi);

        $this->assertInstanceOf(DeviceLinkSession::class, $session);
        $this->assertEquals($sessionId, $session->getSessionId());
        $this->assertEquals($sessionToken, $session->sessionResponse->sessionToken);
        $this->assertEquals($deviceLinkBase, $session->sessionResponse->deviceLinkBase);
        $this->assertEquals($sessionSecret, $session->sessionResponse->sessionSecret);


        $response = json_decode(file_get_contents(self::DEVICE_LINK_AUTH_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));

        $authSessionResponse = $smartId->session($session)->getAuthSession();
        $this->assertInstanceOf(AuthSession::class, $authSessionResponse);
        $this->assertEquals(SessionState::COMPLETE, $authSessionResponse->state);
        $this->assertEquals(SessionEndResult::OK, $authSessionResponse->endResult);
        $this->assertEquals('PNOEE-40404040009-MOCK-Q', $authSessionResponse->documentNumber);
        $this->assertEquals(SignatureProtocol::ACSP_V2, $authSessionResponse->signatureProtocol);
        $this->assertTrue($authSessionResponse->isComplete());
        $this->assertTrue($authSessionResponse->isSuccessful());
        $this->assertEquals($req->getInteractions(), $authSessionResponse->getInteractions());
        $this->assertEquals('https://localhost/callback.php', $authSessionResponse->getInitialCallbackUrl());
        $this->assertEmpty($authSessionResponse->deviceIpAddress);
        $this->assertEquals($rpChallenge, $authSessionResponse->getSignedData());
        $this->assertNotEmpty($authSessionResponse->signature->value);
        $this->assertEquals(SignatureAlgorithm::RSASSA_PSS, $authSessionResponse->signature->signatureAlgorithm);
        $this->assertEquals(FlowType::WEB_TO_APP, $authSessionResponse->signature->flowType);
        $this->assertEquals(InteractionType::DISPLAY_TEXT_AND_PIN, $authSessionResponse->interactionTypeUsed);
        $this->assertNotEmpty($authSessionResponse->certificate->valueInBase64);
    }
}
