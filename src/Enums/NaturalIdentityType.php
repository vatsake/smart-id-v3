<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum NaturalIdentityType: string
{
    case PASSPORT = 'PAS';
    case NATIONAL_ID_NUMBER = 'IDC';
    case NATIONAL_PERSONAL_NUMBER = 'PNO';
}
