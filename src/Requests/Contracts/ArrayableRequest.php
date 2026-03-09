<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests\Contracts;

interface ArrayableRequest
{
    public function toArray(): array;
}
