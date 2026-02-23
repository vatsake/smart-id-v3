<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class CertificatePolicyException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Certificate policies extension is missing or does not contain the required Smart-ID policy OIDs.');
    }
}
