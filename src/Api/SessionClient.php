<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api;

use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\CertificateChoiceSession;
use Vatsake\SmartIdV3\Factories\SessionFactory;
use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Session\SigningSession;

class SessionClient extends ApiClient
{
    private bool $withPolling = false;
    private int $pollIntervalMs = 0;
    private ?LoggerInterface $logger;

    public function __construct(
        private SessionContract $session,
        SmartIdConfig $config
    ) {
        parent::__construct($config);
        $this->logger = $config->getLogger();
    }

    public function withPolling(int $pollIntervalMs): self
    {
        $this->withPolling = true;
        $this->pollIntervalMs = $pollIntervalMs;
        return $this;
    }

    public function getAuthSession(int $timeoutMs = 10000): AuthSession
    {
        $data = $this->fetchSessionData($timeoutMs);
        return SessionFactory::createAuthSession($data, $this->session, $this->config);
    }

    public function getSigningSession(int $timeoutMs = 10000): SigningSession
    {
        $data = $this->fetchSessionData($timeoutMs);
        return SessionFactory::createSigningSession($data, $this->session, $this->config);
    }

    public function getCertChoiceSession(int $timeoutMs = 10000): CertificateChoiceSession
    {
        $data = $this->fetchSessionData($timeoutMs);
        return SessionFactory::createCertChoiceSession($data, $this->session, $this->config);
    }

    private function fetchSessionData(int $timeoutMs): array
    {
        if (!$this->withPolling) {
            return $this->getSingleSessionData($timeoutMs);
        }

        return $this->pollSessionUntilComplete($timeoutMs);
    }

    private function getSingleSessionData(int $timeoutMs): array
    {
        $response = $this->getJson(
            "/session/{$this->session->getSessionId()}",
            ['timeoutMs' => $timeoutMs]
        );

        $data = json_decode($response->getBody()->getContents(), true);

        // This is needed for validation later
        $data['session'] = $this->session;
        return $data;
    }

    private function pollSessionUntilComplete(int $timeoutMs): array
    {
        $sessionId = $this->session->getSessionId();

        $this->logger?->debug("Starting to poll session {$sessionId} every {$this->pollIntervalMs}ms with timeout of {$timeoutMs}ms");
        while (!$this->isSessionComplete($data ?? [])) {
            $data = $this->getSingleSessionData($timeoutMs);

            $this->logger?->debug("Session {$sessionId} still pending, polling again in {$this->pollIntervalMs}ms");
            usleep($this->pollIntervalMs * 1000);
        }
        $this->logger?->debug("Session {$sessionId} is complete, polling stopped");

        return $data;
    }

    private function isSessionComplete(array $data): bool
    {
        $state = $data['state'] ?? null;
        return $state === SessionState::COMPLETE->value;
    }
}
