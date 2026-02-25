<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum SignatureProtocol: string
{
    case ACSP_V2 = 'ACSP_V2';
    case RAW_DIGEST_SIGNATURE = 'RAW_DIGEST_SIGNATURE';
}
