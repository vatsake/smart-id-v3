<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Exceptions;

class OcspException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
