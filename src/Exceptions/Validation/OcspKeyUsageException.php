<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class OcspKeyUsageException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('OCSP responder certificate does not have OCSP signing extended key usage.');
    }
}
