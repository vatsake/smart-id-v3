<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class InitialCallbackUrlParamMismatchException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Initial callback URL unique parameter mismatch.');
    }
}
