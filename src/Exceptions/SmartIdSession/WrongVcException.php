<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class WrongVcException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('User selected the wrong verification code.');
    }
}
