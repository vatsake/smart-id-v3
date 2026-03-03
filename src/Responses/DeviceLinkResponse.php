<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses;

class DeviceLinkResponse
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $sessionToken,
        public readonly string $sessionSecret,
        public readonly string $deviceLinkBase,
    ) {
    }

    public static function fromArray(array $body): self
    {
        self::validate($body);
        return new self(
            sessionId: $body['sessionID'],
            sessionToken: $body['sessionToken'],
            sessionSecret: $body['sessionSecret'],
            deviceLinkBase: $body['deviceLinkBase'],
        );
    }

    private static function validate($body): void
    {
        $required = ['sessionID', 'sessionToken', 'sessionSecret', 'deviceLinkBase'];
        foreach ($required as $field) {
            if (!isset($body[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
    }
}
