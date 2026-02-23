<?php

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class UserRefusedException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('User refused the request.');
    }
}
