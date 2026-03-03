<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses;

class LinkedResponse
{
    public function __construct(
        public readonly string $sessionId,
    ) {
    }

    public static function fromArray(array $body): self
    {
        return new self(sessionId: $body['sessionID']);
    }
}
