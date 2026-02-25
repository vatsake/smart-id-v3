<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Data;

class SessionData
{
    public function __construct(
        public readonly string $state,
        public readonly ?array $result = null,
        public readonly ?string $signatureProtocol = null,
        public readonly ?array $signature = null,
        public readonly ?array $cert = null,
        public readonly ?string $interactionTypeUsed = null,
        public readonly ?string $deviceIp = null,
        public readonly ?array $ignoredProperties = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            state: $data['state'],
            result: $data['result'] ?? null,
            signatureProtocol: $data['signatureProtocol'] ?? null,
            signature: $data['signature'] ?? null,
            cert: $data['cert'] ?? null,
            interactionTypeUsed: $data['interactionTypeUsed'] ?? null,
            deviceIp: $data['deviceIpAddress'] ?? null,
            ignoredProperties: $data['ignoredProperties'] ?? null,
        );
    }
}
