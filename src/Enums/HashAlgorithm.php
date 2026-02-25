<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum HashAlgorithm: string
{
    case SHA_256 = 'SHA-256';
    case SHA_384 = 'SHA-384';
    case SHA_512 = 'SHA-512';

    /**
     * Unable to verify signature
     * @see https://phpseclib.com/docs/rsa#rsasignature_pkcs1
     */
    case SHA3_256 = 'SHA3-256';

    /**
     * Unable to verify signature
     * @see https://phpseclib.com/docs/rsa#rsasignature_pkcs1
     */
    case SHA3_384 = 'SHA3-384';

    /**
     * Unable to verify signature
     * @see https://phpseclib.com/docs/rsa#rsasignature_pkcs1
     */
    case SHA3_512 = 'SHA3-512';

    public function getName(): string
    {
        return strtolower(str_replace('-', '', $this->value));
    }
}
