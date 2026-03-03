<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum SmartIdEnv
{
    case DEMO;
    case PROD;

    public function getBaseUrl(): string
    {
        return match ($this) {
            self::DEMO => 'https://sid.demo.sk.ee/smart-id-rp/v3',
            self::PROD => 'https://rp-api.smart-id.com/v3',
        };
    }

    public function getScheme(): string
    {
        return match ($this) {
            self::DEMO => 'smart-id-demo',
            self::PROD => 'smart-id',
        };
    }
}
