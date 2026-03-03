<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class ServerErrorException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('A server error occurred during the signing process.');
    }
}
