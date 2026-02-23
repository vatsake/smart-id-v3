<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Utils;

class RpChallenge
{
    public static function generate(): string
    {
        return base64_encode(random_bytes(64));
    }
}
