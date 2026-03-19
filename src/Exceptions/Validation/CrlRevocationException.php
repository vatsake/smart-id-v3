<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class CrlRevocationException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Certificate is revoked according to CRL.');
    }
}
