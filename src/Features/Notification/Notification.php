<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features\Notification;

use Vatsake\SmartIdV3\Api\Notification\NotificationAuthClient;
use Vatsake\SmartIdV3\Api\Notification\NotificationSigningClient;
use Vatsake\SmartIdV3\Config\SmartIdConfig;

class Notification
{
    public function __construct(private SmartIdConfig $config) {}

    public function authentication(): NotificationAuthClient
    {
        return new NotificationAuthClient($this->config);
    }

    public function signing(): NotificationSigningClient
    {
        return new NotificationSigningClient($this->config);
    }
}
