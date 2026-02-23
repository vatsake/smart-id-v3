<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions\Validation;

class CertificateQcException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Certificate QC statements extension is missing or does not contain the required Smart-ID QC statements.');
    }
}
