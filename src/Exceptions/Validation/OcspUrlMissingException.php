<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class OcspUrlMissingException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('OCSP URL is missing from the certificate.');
    }
}
