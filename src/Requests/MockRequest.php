<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\MockRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

// Mocking doesn't work at the moment
class MockRequest
{
    use ToArray;

    public readonly string $documentNumber;
    public readonly string $deviceLink;
    public readonly string $flowType;
    public readonly ?string $browserCookie;
    public readonly ?string $initialCallbackUrl;

    public function __construct(array $data)
    {
        $this->documentNumber = $data['documentNumber'];
        $this->deviceLink = $data['deviceLink'];
        $this->flowType = $data['flowType'];
        $this->browserCookie = $data['browserCookie'] ?? null;
        $this->initialCallbackUrl = $data['initialCallbackUrl'] ?? null;
    }

    public static function builder(): MockRequestBuilder
    {
        return new MockRequestBuilder();
    }
}
