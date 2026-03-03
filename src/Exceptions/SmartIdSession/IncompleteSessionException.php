<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class IncompleteSessionException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('The session is not complete yet.');
    }
}
