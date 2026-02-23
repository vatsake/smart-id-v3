<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum DeviceLinkType: string
{
    case QR = 'QR';
    case APP_TO_APP = 'App2App';
    case WEB_TO_APP = 'Web2App';
}
