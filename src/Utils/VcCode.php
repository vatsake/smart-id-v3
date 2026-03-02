<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Utils;

class VcCode
{
    public static function fromRpChallenge(string $rpChallenge): string
    {
        $rpChallengeBase64 = base64_decode($rpChallenge);
        $sha256Hash = hash('sha256', $rpChallengeBase64, true);
        $result = hexdec(bin2hex(substr($sha256Hash, -2))) % 10000;
        $verificationCode = sprintf('%04d', $result);
        return $verificationCode;
    }
}
