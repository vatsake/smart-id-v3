<?php

namespace Vatsake\SmartIdV3\Tests\Validators;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\InitialCallbackUrlParamMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\SessionSecretMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\UserChallengeMismatchException;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\Validators\Session\CallbackUrlValidator;

class CallbackUrlValidatorTest extends TestCase
{
    use MockConfigTrait;

    private const DEVICE_LINK_AUTH_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/auth/device-link/session-response.json';


    private function getAuthSession()
    {
        $sessionId = '1234567890';
        $sessionSecret = 'Mu5+ikvpWvtQ68d6aVbq2LO8';
        $sessionToken = '8TJptRRLZUXahsj41JY2fe76';
        $deviceLinkBase = 'https://sid.demo.sk.ee/device-link';
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
        $session = $smartId->deviceLink()->authentication()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');

        $response = json_decode(file_get_contents(self::DEVICE_LINK_AUTH_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));
        return $smartId->session($session)->getAuthSession();
    }

    private function createCallbackUrlValidator(
        ?string $sessionSecretDigest = 'NW4wcCrORRaa0npPmDDNfRAxMQ6CFdRLI8-pSGfdRl4',
        ?string $userChallenge = 'bnknvqeB2MztUFO1iqtpE1omKGZX8XzeBQ-ZGru-ppg',
        ?string $callbackUrlParam = '5dFhg1JW2sE',
        ?string $receivedCallbackUrlParam = '5dFhg1JW2sE'
    ): CallbackUrlValidator {
        return new CallbackUrlValidator(
            $this->getAuthSession(),
            null,
            $sessionSecretDigest,
            $userChallenge,
            $callbackUrlParam,
            $receivedCallbackUrlParam,
            $this->createMockConfig()
        );
    }

    private function getReflectionMethod(string $methodName): \ReflectionMethod
    {
        return new \ReflectionMethod(CallbackUrlValidator::class, $methodName);
    }

    public function testValidateSessionSecretWithValidDigest()
    {
        $validator = $this->createCallbackUrlValidator();
        $method = $this->getReflectionMethod('validateSessionSecret');

        $method->invoke($validator);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateSessionSecretThrowsExceptionForInvalidDigest()
    {
        $validator = $this->createCallbackUrlValidator(sessionSecretDigest: 'Hello');
        $method = $this->getReflectionMethod('validateSessionSecret');

        $this->expectException(SessionSecretMismatchException::class);
        $method->invoke($validator);
    }

    public function testValidateCallbackUrlParamWithValidValue()
    {
        $validator = $this->createCallbackUrlValidator();
        $method = $this->getReflectionMethod('validateCallbackQueryParam');

        $method->invoke($validator);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateCallbackUrlParamThrowsExceptionForInvalidValue()
    {
        $validator = $this->createCallbackUrlValidator(receivedCallbackUrlParam: 'invalid param value');
        $method = $this->getReflectionMethod('validateCallbackQueryParam');

        $this->expectException(InitialCallbackUrlParamMismatchException::class);
        $method->invoke($validator);
    }

    public function testValidateUserChallengeWithValidValue()
    {
        $validator = $this->createCallbackUrlValidator();
        $method = $this->getReflectionMethod('validateUserChallenge');

        $method->invoke($validator);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateUserChallengeThrowsExceptionForInvalidValue()
    {
        $validator = $this->createCallbackUrlValidator(userChallenge: 'invalid-challenge');
        $method = $this->getReflectionMethod('validateUserChallenge');

        $this->expectException(UserChallengeMismatchException::class);
        $method->invoke($validator);
    }
}
