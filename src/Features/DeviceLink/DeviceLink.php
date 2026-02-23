<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\DeviceLink;

use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkAuthClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkMockClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkSigningClient;

class DeviceLink
{
    public function __construct(private SmartIdConfig $config) {}

    public function auth(): DeviceLinkAuthClient
    {
        return new DeviceLinkAuthClient($this->config);
    }

    public function signing(): DeviceLinkSigningClient
    {
        return new DeviceLinkSigningClient($this->config);
    }

    /**
     * Used for mocking mobile device in demo environment
     */
    public function mockDevice(): DeviceLinkMockClient
    {
        return new DeviceLinkMockClient($this->config);
    }
}
