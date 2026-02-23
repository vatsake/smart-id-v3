<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests\Ocsp;

use Vatsake\SmartIdV3\Builders\Request\Ocsp\OcspRequestBuilder;

class OcspRequest
{
    public function __construct(private string $request) {}

    public static function builder(): OcspRequestBuilder
    {
        return new OcspRequestBuilder();
    }

    public function getBody(): string
    {
        return $this->request;
    }
}
