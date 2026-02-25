<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class UserNotFoundException extends HttpException
{
    public function __construct(
        string $endpoint,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $extraMessage = ', no user found with the provided identifier';
        parent::__construct($endpoint, 404, $extraMessage, $code, $previous);
    }
}
