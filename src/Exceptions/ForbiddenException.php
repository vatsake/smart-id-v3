<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class ForbiddenException extends HttpException
{
    public function __construct(
        string $endpoint,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $reasons = [
            '- Relying Party has no permission to invoke operations on accounts with ADVANCED certificates',
            '- Relying Party has no permission to use requested capability',
            '- Relying Party has no permission to access the requested feature or endpoint',
            '- Relying Party with given UUID does not exist'
        ];

        $extraMessage = sprintf(
            '. Possible reasons: %s%s',
            implode('; ', $reasons),
            !empty($body) ? sprintf('. Details: %s', json_encode($body)) : ''
        );
        parent::__construct($endpoint, 403, $extraMessage, $code, $previous);
    }
}
