<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\DeviceLink;

use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\Responses\LinkedResponse;

class LinkedSession implements SessionContract
{
    public function __construct(
        public readonly LinkedRequest $sessionRequest,
        public readonly LinkedResponse $sessionResponse,
    ) {
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
        return '';
    }
}
