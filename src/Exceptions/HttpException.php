<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class HttpException extends \Exception
{
    public function __construct(
        string $endpoint,
        int $statusCode,
        string $extraMessage = "",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $errorMessage = sprintf(
            'HTTP request to %s failed with status %d%s.',
            $endpoint,
            $statusCode,
            $extraMessage
        );
        parent::__construct($errorMessage, $code, $previous);
    }
}
