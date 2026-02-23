<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class ClientTooOldException extends HttpException
{
    public function __construct(
        string $endpoint,
        array $body = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $extraMessage = '. The library is using an outdated API version that is no longer supported. Please update the library';
        parent::__construct($endpoint, 480, $extraMessage, $code, $previous);
    }
}
