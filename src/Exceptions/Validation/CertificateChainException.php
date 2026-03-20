<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class CertificateChainException extends ValidationException
{
    public function __construct(string $cn)
    {
        parent::__construct('Certificate is not trusted. Certificate chain validation failed for subject: ' . $cn);
    }
}
