<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class OcspCertificateRevocationException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('OCSP certificate status is not good.');
    }
}
