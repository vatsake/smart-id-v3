<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\DeviceLink;

use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkAuthClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkMockClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkSigningClient;
use Vatsake\SmartIdV3\Factories\ClientFactory;

class DeviceLink
{
    private ClientFactory $clientFactory;

    public function __construct(private SmartIdConfig $config)
    {
        $this->clientFactory = new ClientFactory($config);
    }

    public function authentication(): DeviceLinkAuthClient
    {
        return $this->clientFactory->createDeviceLinkAuthClient();
    }

    public function signing(): DeviceLinkSigningClient
    {
        return $this->clientFactory->createDeviceLinkSigningClient();
    }

    /**
     * Used for mocking mobile device in demo environment
     */
    public function mockDevice(): DeviceLinkMockClient
    {
        return $this->clientFactory->createDeviceLinkMockClient();
    }
}
