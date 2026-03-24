<?php

namespace Vatsake\SmartIdV3\Tests\Validators;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Data\SignatureData;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Responses\Signature\AcspV2Signature;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;
use Vatsake\SmartIdV3\Validators\Session\SignatureValidator;

class SignatureValidatorTest extends TestCase
{
    use MockConfigTrait;

    private const NOTIFICATION_AUTH_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/auth/notification/session-response.json';
    private const NOTIFICATION_AUTH2_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/auth/notification/session-response-pkcs1.json';
    private const NOTIFICATION_SIGNING_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/signing/notification/session-response.json';
    private const NOTIFICATION_SIGNING2_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/signing/notification/session-response-pkcs1.json';

    private function getAuthSession(string $rpChallenge, string $path)
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $req = NotificationAuthRequest::builder()
            ->withInteractions('Hello world')
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();
        $session = $smartId->notification()->authentication()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');

        $response = json_decode(file_get_contents($path), true);
        $smartId = new SmartId($this->createMockConfig($response));
        return $smartId->session($session)->getAuthSession();
    }

    private function getSigningSession(string $rawData, string $path)
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId, 'vc' => ['type' => 'numeric4', 'value' => '1234']]));

        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData($rawData, HashAlgorithm::SHA_512)
            ->build();
        $session = $smartId->notification()->signing()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');

        $response = json_decode(file_get_contents($path), true);
        $smartId = new SmartId($this->createMockConfig($response));
        return $smartId->session($session)->getSigningSession();
    }

    public function testAuthPayload()
    {
        $reflection = new \ReflectionMethod(SignatureValidator::class, 'getAcspV2Payload');
        $signValidator = new SignatureValidator($this->getAuthSession('q1miCUX2v3RAQSycBUWdJrRtG6GR1z55c2oIAdjynk3IIquT4CfoSzcdzbLQDL9eTDojGpy+8QKS1jgWzTB58g==', self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH), $this->createMockConfig());
        $payload = $reflection->invoke($signValidator);
        $this->assertEquals('smart-id-demo|ACSP_V2|2BRq6GXciED/sysSj7n6rxzA|q1miCUX2v3RAQSycBUWdJrRtG6GR1z55c2oIAdjynk3IIquT4CfoSzcdzbLQDL9eTDojGpy+8QKS1jgWzTB58g==|TTb4rM24GEIvtddfOqA-gOUwdJSXKrj5JeVnRtPv7tI|REVNTw==||miue+Qk6dw2zpM6CL4Uz7XkKqWfTUGA3L/DDUXV96AE=|displayTextAndPIN||Notification', $payload);
    }

    public function testValidatePSSAuthSignature()
    {
        $signValidator = new SignatureValidator($this->getAuthSession('q1miCUX2v3RAQSycBUWdJrRtG6GR1z55c2oIAdjynk3IIquT4CfoSzcdzbLQDL9eTDojGpy+8QKS1jgWzTB58g==', self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH), $this->createMockConfig());
        $signValidator->validate();
        $this->expectNotToPerformAssertions();
    }

    public function testValidatePKCS1AuthSignature()
    {
        $signValidator = new SignatureValidator($this->getAuthSession('minitaAEJHkuOLUpZ4DawKQJnBH0pQiT3Xtqys3KPHKwANmilKp/B/KruVWU3EwLnTtwE13CAHJlwwwkEERVfw==', self::NOTIFICATION_AUTH2_RESPONSE_FILE_PATH), $this->createMockConfig());
        $signValidator->validate();
        $this->expectNotToPerformAssertions();
    }

    public function testValidateSigningSignature()
    {
        $signValidator = new SignatureValidator($this->getSigningSession('test 1234', self::NOTIFICATION_SIGNING_RESPONSE_FILE_PATH), $this->createMockConfig());
        $signValidator->validate();
        $this->expectNotToPerformAssertions();
    }

    public function testValidatePKCS1SigningSignature()
    {
        $signValidator = new SignatureValidator($this->getSigningSession('test 1234', self::NOTIFICATION_SIGNING2_RESPONSE_FILE_PATH), $this->createMockConfig());
        $signValidator->validate();
        $this->expectNotToPerformAssertions();
    }

    public function testValidateInvalidSigningSignature()
    {
        $signValidator = new SignatureValidator($this->getSigningSession('test 12345', self::NOTIFICATION_SIGNING_RESPONSE_FILE_PATH), $this->createMockConfig());
        $this->expectException(SignatureException::class);
        $signValidator->validate();
    }

    public function testValidateInvalidAuthSignature()
    {
        $signValidator = new SignatureValidator($this->getAuthSession('test 12345', self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH), $this->createMockConfig());
        $this->expectException(SignatureException::class);
        $signValidator->validate();
    }
}
