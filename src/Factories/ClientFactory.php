<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Factories;

use Vatsake\SmartIdV3\Api\CertificateClient;
use Vatsake\SmartIdV3\Api\SessionClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkAuthClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkSigningClient;
use Vatsake\SmartIdV3\Api\DeviceLink\DeviceLinkMockClient;
use Vatsake\SmartIdV3\Api\Notification\NotificationAuthClient;
use Vatsake\SmartIdV3\Api\Notification\NotificationSigningClient;
use Vatsake\SmartIdV3\Api\Ocsp\OcspClient;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Features\SessionContract;


class ClientFactory
{
    public function __construct(private SmartIdConfig $config) {}

    public function createCertificateClient(): CertificateClient
    {
        return new CertificateClient($this->config);
    }

    public function createSessionClient(SessionContract $session): SessionClient
    {
        return new SessionClient($session, $this->config);
    }

    public function createDeviceLinkAuthClient(): DeviceLinkAuthClient
    {
        return new DeviceLinkAuthClient($this->config);
    }

    public function createDeviceLinkSigningClient(): DeviceLinkSigningClient
    {
        return new DeviceLinkSigningClient($this->config);
    }

    public function createDeviceLinkMockClient(): DeviceLinkMockClient
    {
        return new DeviceLinkMockClient($this->config);
    }

    public function createNotificationAuthClient(): NotificationAuthClient
    {
        return new NotificationAuthClient($this->config);
    }

    public function createNotificationSigningClient(): NotificationSigningClient
    {
        return new NotificationSigningClient($this->config);
    }

    public function createOcspClient(): OcspClient
    {
        return new OcspClient($this->config);
    }
}
