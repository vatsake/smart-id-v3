<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum SignatureAlgorithm: string
{
    case RSASSA_PSS = 'rsassa-pss';

    /** Deprecated algorithms */
    case SHA256_RSA = 'sha256WithRSAEncryption';
    case SHA384_RSA = 'sha384WithRSAEncryption';
    case SHA512_RSA = 'sha512WithRSAEncryption';
}
