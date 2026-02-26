<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Utils;

class UrlSafe
{
    public static function toUrlSafe(string $data): string
    {
        return rtrim(strtr($data, '+/', '-_'), '=');
    }
}
