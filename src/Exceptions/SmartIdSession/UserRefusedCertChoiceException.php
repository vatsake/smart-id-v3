<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class UserRefusedCertChoiceException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('User refused to choose a certificate.');
    }
}
