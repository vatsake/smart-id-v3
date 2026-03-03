<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses;

class NotificationResponse
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $vc,
    ) {
    }

    public static function fromArray(array $body): self
    {
        return new self(
            sessionId: $body['sessionID'],
            vc: $body['vc']['value'] ?? '', // Certificate choice does not return a VC
        );
    }
}
