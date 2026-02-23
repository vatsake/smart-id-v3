<?php

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class ProtocolFailureException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('A logical error occurred in the signing protocol.');
    }
}
