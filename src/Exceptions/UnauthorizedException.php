<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class UnauthorizedException extends HttpException
{
    public function __construct(
        string $endpoint,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $extraMessage = ', ensure your relying party credentials are correct';
        parent::__construct($endpoint, 401, $extraMessage, $code, $previous);
    }
}
