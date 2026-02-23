<?php

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class DocumentUnusableException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('Request failed. User must either check his/her Smart-ID mobile application or turn to customer support for getting the exact reason.');
    }
}
