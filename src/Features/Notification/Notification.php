<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\Notification;

use Vatsake\SmartIdV3\Api\Notification\NotificationAuthClient;
use Vatsake\SmartIdV3\Api\Notification\NotificationSigningClient;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Factories\ClientFactory;

class Notification
{
    private ClientFactory $clientFactory;

    public function __construct(private SmartIdConfig $config)
    {
        $this->clientFactory = new ClientFactory($config);
    }

    public function authentication(): NotificationAuthClient
    {
        return $this->clientFactory->createNotificationAuthClient();
    }

    public function signing(): NotificationSigningClient
    {
        return $this->clientFactory->createNotificationSigningClient();
    }
}
