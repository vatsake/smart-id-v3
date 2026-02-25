<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class MaintenanceException extends HttpException
{
    public function __construct(
        string $endpoint,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $extraMessage = ', the system is currently under maintenance';
        parent::__construct($endpoint, 503, $extraMessage, $code, $previous);
    }
}
