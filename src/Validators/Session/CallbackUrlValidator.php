<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators\Session;

use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Exceptions\Validation\InitialCallbackUrlParamMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\SessionSecretMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\UserChallengeMismatchException;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Utils\UrlSafe;

class CallbackUrlValidator implements SessionValidatorInterface
{
    public function __construct(
        private SigningSession|AuthSession $session,
        private ?LoggerInterface $logger,
        private string $sessionSecretDigest,
        private string $userChallengeVerifier,
        private string $expectedQueryParamValue,
        private string $queryParamValue
    ) {
    }

    public function validate(): void
    {
        $this->validateSessionSecret();
        $this->validateCallbackQueryParam();
        if ($this->session instanceof AuthSession) {
            $this->validateUserChallenge();
        }
    }

    private function validateSessionSecret(): void
    {
        $secret = base64_decode($this->session->getSessionSecret());
        $hash = hash('sha256', $secret, true);
        $urlSafeHash = UrlSafe::toUrlSafe(base64_encode($hash));

        if ($urlSafeHash !== $this->sessionSecretDigest) {
            $this->logger?->debug('Session secret digest validation failed', ['expected' => $this->sessionSecretDigest, 'actual' => $urlSafeHash]);
            throw new SessionSecretMismatchException();
        }

        $this->logger?->debug('Session secret digest validation successful.');
    }

    private function validateCallbackQueryParam(): void
    {
        if ($this->expectedQueryParamValue !== $this->queryParamValue) {
            $this->logger?->debug('Callback URL query parameter validation failed', ['expected' => $this->expectedQueryParamValue, 'actual' => $this->queryParamValue]);
            throw new InitialCallbackUrlParamMismatchException();
        }
        $this->logger?->debug('Callback URL query parameter validation successful.');
    }

    private function validateUserChallenge(): void
    {
        $userChallenge = $this->session->signature->userChallenge;
        $challengeHash = hash('sha256', $this->userChallengeVerifier, true);
        $urlSafeChallengeHash = UrlSafe::toUrlSafe(base64_encode($challengeHash));

        if ($userChallenge !== $urlSafeChallengeHash) {
            $this->logger?->debug('User challenge verifier validation failed', ['expected' => $userChallenge, 'actual' => $urlSafeChallengeHash]);
            throw new UserChallengeMismatchException();
        }
        $this->logger?->debug('User challenge verifier validation successful.');
    }
}
