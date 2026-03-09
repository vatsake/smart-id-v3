<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests\Contracts;

interface NotificationRequest extends ArrayableRequest
{
    /**
     * For signing requests, returns the raw data that is signed. For authentication requests, returns rpchallenge (base64) that is signed.
     */
    public function getSignedData(): string;

    /**
     * Interactions in base64
     */
    public function getInteractions(): string;
}
