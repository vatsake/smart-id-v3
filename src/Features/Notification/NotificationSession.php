<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\Notification;

use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Requests\Contracts\NotificationRequest;
use Vatsake\SmartIdV3\Responses\NotificationResponse;

class NotificationSession implements SessionContract
{
    public function __construct(
        public readonly NotificationRequest $request,
        public readonly NotificationResponse $response,
    ) {}

    public function getSessionId(): string
    {
        return $this->response->sessionId;
    }


    public function getSignedData(): string
    {
        return $this->request->getSignedData();
    }

    public function getInteractions(): string
    {
        return $this->request->getInteractions();
    }

    public function getSessionSecret(): string
    {
        return '';
    }

    public function getInitialCallbackUrl(): string
    {
        return '';
    }
}
