<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class UnknownSignatureAlgorithmOidException extends ValidationException
{
    public function __construct(string $oid)
    {
        parent::__construct('Unknown signature algorithm OID: ' . $oid);
    }
}
