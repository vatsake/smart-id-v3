<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\DeviceLink;

use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;
use Vatsake\SmartIdV3\Responses\DeviceLinkResponse;
use Vatsake\SmartIdV3\Utils\UrlSafe;

class DeviceLinkSession implements SessionContract
{
    private const VERSION = '1.0';
    private const PAYLOAD_PREFIX = 'smart-id';

    private ?LoggerInterface $logger = null;

    private int $startedAt;

    public readonly string $relyingPartyName;

    public function __construct(
        public readonly DeviceLinkRequest $sessionRequest,
        public readonly DeviceLinkResponse $sessionResponse,
        SmartIdConfig $config,
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

        $deviceLink = $this->sessionResponse->deviceLinkBase . '?'
            . 'deviceLinkType=' . $deviceLinkType->value
            . ($isQr ? '&elapsedSeconds=' . $this->getElapsedSeconds() : '')
            . '&sessionToken=' . $this->sessionResponse->sessionToken
            . '&sessionType=' . $this->sessionRequest->getSessionType()
            . '&version=' . self::VERSION
            . '&lang=' . $fallbackLang;

        return $deviceLink;
    }

    private function buildPayload(string $deviceLink): string
    {
        $components = [
            self::PAYLOAD_PREFIX,
            $this->sessionRequest->getSignatureProtocol(),
            $this->getSignedData(),
            base64_encode($this->relyingPartyName),
            '', // Broker
            $this->getInteractions(),
            $this->sessionRequest->getInitialCallbackUrl(),
            $deviceLink
        ];
        $payload = implode('|', $components);

        $this->logger?->debug('Generated payload for device link', ['payload' => $payload]);
        return $payload;
    }

    private function generateAuthCode(string $deviceLink): string
    {
        $payload = $this->buildPayload($deviceLink);
        $sessionSecret = base64_decode($this->sessionResponse->sessionSecret);

        $hash = base64_encode(hash_hmac('sha256', $payload, $sessionSecret, true));
        $urlSafeHash = UrlSafe::toUrlSafe(rtrim(strtr($hash, '+/', '-_'), '='));

        $this->logger?->debug('Generated auth code for device link', ['authCode' => $urlSafeHash]);
        return $urlSafeHash;
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
        return $this->sessionResponse->sessionId;
    }

    public function getSignedData(): string
    {
        return $this->sessionRequest->getSignedData();
    }

    public function getInteractions(): string
    {
        return $this->sessionRequest->getInteractions();
    }

    public function getInitialCallbackUrl(): string
    {
        return $this->sessionRequest->getInitialCallbackUrl();
    }

    public function getSessionSecret(): string
    {
        return $this->sessionResponse->sessionSecret;
    }
}
