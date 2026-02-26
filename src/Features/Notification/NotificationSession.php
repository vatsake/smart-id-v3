<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\Notification;

use Vatsake\SmartIdV3\Features\SessionContract;

class NotificationSession implements SessionContract
{
    public function __construct(
        private string $sessionId,
        private string $signedData = '',
        private string $interactions = '',
        private string $initialCallbackUrl = '',
        private string $sessionSecret = '',
    ) {
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getSignedData(): string
    {
        return $this->signedData;
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
