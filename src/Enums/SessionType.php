<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum SessionType: string
{
    case AUTH = 'auth';
    case SIGN = 'sign';
    case CERT = 'cert';
}
