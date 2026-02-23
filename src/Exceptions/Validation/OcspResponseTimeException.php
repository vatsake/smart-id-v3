<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class OcspResponseTimeException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('OCSP response time is outside the acceptable skew range.');
    }
}
