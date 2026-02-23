<?php

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class UserRefusedInteractionException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('User cancelled on the interaction screen.');
    }
}
