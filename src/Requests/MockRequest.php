<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\MockRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

// Mocking doesn't work at the moment
class MockRequest
{
    use ToArray;

    public function __construct(
        public readonly string $documentNumber,
        public readonly string $deviceLink,
        public readonly string $flowType,
        public readonly ?string $browserCookie = null,
        public readonly ?string $initialCallbackUrl = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['documentNumber'],
            $data['deviceLink'],
            $data['flowType'],
            $data['browserCookie'] ?? null,
            $data['initialCallbackUrl'] ?? null
        );
    }

    public static function builder(): MockRequestBuilder
    {
        return new MockRequestBuilder();
    }
}
