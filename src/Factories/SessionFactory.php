<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Factories;

use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\BaseSession;
use Vatsake\SmartIdV3\Session\CertificateChoiceSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Features\SessionContract;

class SessionFactory
{
    public static function createAuthSession(array $data, SessionContract $session, SmartIdConfig $config): AuthSession
    {
        return self::createTypedSession($data, $session, $config, AuthSession::class);
    }

    public static function createSigningSession(array $data, SessionContract $session, SmartIdConfig $config): SigningSession
    {
        return self::createTypedSession($data, $session, $config, SigningSession::class);
    }

    public static function createCertChoiceSession(array $data, SessionContract $session, SmartIdConfig $config): CertificateChoiceSession
    {
        return self::createTypedSession($data, $session, $config, CertificateChoiceSession::class);
    }

    private static function createTypedSession(array $data, SessionContract $session, SmartIdConfig $config, string $sessionClass): BaseSession
    {
        self::validate($data);
        return new $sessionClass(
            state: $data['state'],
            session: $session,
            config: $config,
            result: $data['result'] ?? null,
            signatureProtocol: $data['signatureProtocol'] ?? null,
            signature: $data['signature'] ?? null,
            cert: $data['cert'] ?? null,
            interactionTypeUsed: $data['interactionTypeUsed'] ?? null,
            deviceIp: $data['deviceIpAddress'] ?? null,
            ignoredProperties: $data['ignoredProperties'] ?? null,
        );
    }

    private static function validate(array $data): void
    {
        if (!isset($data['state'])) {
            throw new \InvalidArgumentException('Missing state in session response.');
        }

        if ($data['state'] === SessionState::COMPLETE->value) {
            if (!isset($data['result']['endResult'])) {
                throw new \InvalidArgumentException('Complete session missing endResult.');
            }

            if ($data['result']['endResult'] === SessionEndResult::OK->value) {
                if (!isset($data['cert']) || $data['cert'] === null) {
                    throw new \InvalidArgumentException('OK session missing certificate.');
                }
                if (!isset($data['signature'])) {
                    throw new \InvalidArgumentException('OK session missing signature.');
                }
            }
        }
    }
}
