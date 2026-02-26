<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class UserChallengeMismatchException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('User challenge mismatch.');
    }
}
