<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class CrlUrlMissingException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('CRL URL is missing from the certificate.');
    }
}
