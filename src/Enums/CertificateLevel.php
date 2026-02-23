<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum CertificateLevel: string
{
    case QUALIFIED = 'QUALIFIED';
    case ADVANCED = 'ADVANCED';
    case QSCD = 'QSCD';
}
