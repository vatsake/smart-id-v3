<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Utils;

class PemFormatter
{
    private const string PEM_HEADER = "-----BEGIN CERTIFICATE-----";
    private const string PEM_FOOTER = "-----END CERTIFICATE-----";

    public static function addPemHeaders(string $value): string
    {
        $value = self::stripPemHeaders($value);

        $pem = self::PEM_HEADER . "\n";
        $pem .= chunk_split($value, 64, "\n");
        $pem .= self::PEM_FOOTER . "\n";
        return $pem;
    }

    public static function stripPemHeaders(string $pemCert): string
    {
        $base64value = str_replace([self::PEM_HEADER, self::PEM_FOOTER, "\n", "\r"], '', $pemCert);
        return $base64value;
    }
}
