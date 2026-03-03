<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class SessionTimeoutException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('Session timed out.');
    }
}
