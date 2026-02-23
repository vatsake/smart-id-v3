<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class OcspSignatureException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('OCSP response signature validation failed.');
    }
}
