<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class BadRequestException extends HttpException
{
    public function __construct(
        string $endpoint,
        array $body,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $extraMessage = sprintf(', details: %s', json_encode($body['errors'] ?? $body));
        parent::__construct($endpoint, 400, $extraMessage, $code, $previous);
    }
}
