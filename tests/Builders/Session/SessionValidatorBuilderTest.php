<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Session;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Session\SessionValidatorBuilder;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Data\SessionData;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;

class SessionValidatorBuilderTest extends TestCase
{
    use MockConfigTrait;

    private function createMockSessionData(): SessionData
    {
        return new SessionData(
            state: SessionState::COMPLETE->value,
            result: [
                'endResult' => SessionEndResult::USER_REFUSED->value
            ],
            signatureProtocol: 'rawDigestSignature',
            signature: null,
            cert: null,
            interactionTypeUsed: null,
            deviceIp: null,
            ignoredProperties: null,
        );
    }

    private function createMockSessionContract()
    {
        $mock = $this->createMock(\Vatsake\SmartIdV3\Features\SessionContract::class);
        $mock->method('getSessionId')->willReturn('550e8400-e29b-41d4-a716-446655440000');
        $mock->method('getSignedData')->willReturn('test_signed_data');
        $mock->method('getInteractions')->willReturn('');
        $mock->method('getInitialCallbackUrl')->willReturn('');
        $mock->method('getSessionSecret')->willReturn('');
        return $mock;
    }

    public function testRevocationValidationCanBeDisabled(): void
    {
        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $result = $builder->withRevocationValidation(false);

        $this->assertInstanceOf(SessionValidatorBuilder::class, $result);
    }

    public function testCallbackUrlValidationWithValidParameters(): void
    {
        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $result = $builder->withCallbackUrlValidationParameters(
            true,
            'session_secret_digest',
            'user_challenge_verifier',
            'expected_param_value',
            'query_param_value'
        );

        $this->assertInstanceOf(SessionValidatorBuilder::class, $result);
        $this->assertSame($builder, $result);
    }

    public function testCallbackUrlValidationDisabledWithoutParameters(): void
    {
        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $result = $builder->withCallbackUrlValidationParameters(false);

        $this->assertInstanceOf(SessionValidatorBuilder::class, $result);
    }

    public function testCallbackUrlValidationMissingSessionSecretDigest(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session secret digest and user challenge verifier must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidationParameters(
            true,
            null,
            'user_challenge_verifier',
            'expected_param_value',
            'query_param_value'
        );
    }

    public function testCallbackUrlValidationMissingUserChallengeVerifier(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Session secret digest and user challenge verifier must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidationParameters(
            true,
            'session_secret_digest',
            null,
            'expected_param_value',
            'query_param_value'
        );
    }

    public function testCallbackUrlValidationMissingQueryParamValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Query parameter value and expected value must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidationParameters(
            true,
            'session_secret_digest',
            'user_challenge_verifier',
            'expected_param_value',
            null
        );
    }

    public function testCallbackUrlValidationMissingExpectedQueryParamValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Query parameter value and expected value must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidationParameters(
            true,
            'session_secret_digest',
            'user_challenge_verifier',
            null,
            'query_param_value'
        );
    }

    public function testCallbackUrlValidationUsingUrlMissingUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('URL and query parameter name must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidation(
            true,
            null,
            'user123',
            'uid'
        );
    }

    public function testCallbackUrlValidationUsingUrlMissingParamName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('URL and query parameter name must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidation(
            true,
            'https://example.com/callback?uid=user123',
            'user123',
            null
        );
    }

    public function testCallbackUrlValidationUsingUrlMissingSessionSecretDigestParam(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Query parameter 'sessionSecretDigest' not found in callback URL.");

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidation(
            true,
            'https://example.com/callback?uid=user123&userChallengeVerifier=challenge',
            'user123',
            'uid'
        );
    }

    public function testCallbackUrlValidationUsingUrlMissingUserChallengeVerifierParam(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Query parameter 'userChallengeVerifier' not found in callback URL.");

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidation(
            true,
            'https://example.com/callback?uid=user123&sessionSecretDigest=secret',
            'user123',
            'uid'
        );
    }

    public function testCallbackUrlValidationUsingUrlMissingCustomParam(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Query parameter 'uid' not found in callback URL.");

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidation(
            true,
            'https://example.com/callback?sessionSecretDigest=secret&userChallengeVerifier=challenge',
            'user123',
            'uid'
        );
    }

    public function testCallbackUrlValidationUsingUrlMissingExpectedValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected query parameter value must be provided');

        $config = $this->createMockConfig();
        $sessionData = $this->createMockSessionData();
        $sessionContract = $this->createMockSessionContract();

        $session = new AuthSession($sessionData, $sessionContract, $config);
        $builder = new SessionValidatorBuilder($session, $config);
        $builder->withCallbackUrlValidation(
            true,
            'https://example.com/callback?uid=user123&sessionSecretDigest=secret&userChallengeVerifier=challenge',
            null,
            'uid'
        );
    }
}
