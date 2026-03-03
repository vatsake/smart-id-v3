<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class RequiredInteractionNotSupportedByAppException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('User app version does not support any of the required interactions.');
    }
}
