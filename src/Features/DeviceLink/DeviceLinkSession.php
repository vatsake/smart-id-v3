<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\DeviceLink;

use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Enums\SessionType;
use Vatsake\SmartIdV3\Features\SessionContract;

class DeviceLinkSession implements SessionContract
{
    private const VERSION = '1.0';
    private const PAYLOAD_PREFIX = 'smart-id';

    private ?LoggerInterface $logger = null;

    private int $startedAt;

    public readonly string $relyingPartyName;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $sessionToken,
        public readonly string $sessionSecret,
        public readonly string $deviceLinkBase,
        public readonly SessionType $sessionType,
        public readonly string $signatureProtocol,
        public readonly string $digest,
        public readonly string $interactions,
        private string $originalData,
        SmartIdConfig $config,
        public readonly string $initialCallbackUrl = '',
    ) {
        $this->startedAt = time();
        $this->relyingPartyName = $config->getRelyingPartyName();
        $this->logger = $config->getLogger();
    }

    private function getElapsedSeconds(): int
    {
        return time() - $this->startedAt;
    }

    private function buildDeviceLink(string $fallbackLang, DeviceLinkType $deviceLinkType): string
    {
        $isQr = $deviceLinkType === DeviceLinkType::QR;
        $deviceLink = $this->deviceLinkBase . '?'
            . 'deviceLinkType=' . $deviceLinkType->value
            . ($isQr ? '&elapsedSeconds=' . $this->getElapsedSeconds() : '')
            . '&sessionToken=' . $this->sessionToken
            . '&sessionType=' . $this->sessionType->value
            . '&version=' . self::VERSION
            . '&lang=' . $fallbackLang;

        $this->logger?->debug('Built device link URL', ['deviceLink' => $deviceLink]);
        return $deviceLink;
    }

    private function buildPayload(string $deviceLink): string
    {
        $components = [
            self::PAYLOAD_PREFIX,
            $this->signatureProtocol,
            $this->digest,
            base64_encode($this->relyingPartyName),
            '',
            $this->interactions,
            $this->initialCallbackUrl,
            $deviceLink
        ];
        $payload = implode('|', $components);

        $this->logger?->debug('Generated payload for device link', ['payload' => $payload]);
        return $payload;
    }

    private function generateAuthCode(string $deviceLink): string
    {
        $payload = $this->buildPayload($deviceLink);
        $sessionSecret = base64_decode($this->sessionSecret);
        $hash = hash_hmac('sha256', $payload, $sessionSecret, true);
        $this->logger?->debug('Generated auth code for device link', ['authCode' => $hash]);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    public function getDeviceLink(DeviceLinkType $deviceLinkType, string $fallbackLang = 'eng'): string
    {
        $deviceLink = $this->buildDeviceLink($fallbackLang, $deviceLinkType);
        $authCode = $this->generateAuthCode($deviceLink);
        $fullLink = $deviceLink . '&authCode=' . $authCode;
        $this->logger?->debug('Generated device link with auth code', ['deviceLink' => $fullLink]);
        return $fullLink;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getSignedData(): string
    {
        return $this->originalData;
    }

    public function getInteractions(): string
    {
        return $this->interactions;
    }

    public function getSessionSecret(): string
    {
        return $this->sessionSecret;
    }

    /**
     * Only populated for device link flows when using App2App/Web2App flows
     */
    public function getInitialCallbackUrl(): string
    {
        return $this->initialCallbackUrl;
    }
}
