<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3;

use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Api\SessionClient;
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLink;
use Vatsake\SmartIdV3\Features\Notification\Notification;
use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Factories\ClientFactory;
use Vatsake\SmartIdV3\Responses\Certificate;

class SmartId
{
    private ClientFactory $clientFactory;

    public function __construct(private SmartIdConfig $config)
    {
        $this->clientFactory = new ClientFactory($config);
    }

    /**
     * Device link flow
     *
     * @see https://sk-eid.github.io/smart-id-documentation/rp-api/device_link_flows.html
     */
    public function deviceLink(): DeviceLink
    {
        return new DeviceLink($this->config);
    }

    /**
     * Notification flow
     *
     * @see https://sk-eid.github.io/smart-id-documentation/rp-api/notification_based_flows.html
     */
    public function notification(): Notification
    {
        return new Notification($this->config);
    }

    /**
     * Signing certificate retrieval
     */
    public function getSigningCertificate(string $documentNo): Certificate
    {
        return $this->clientFactory->createCertificateClient()->getSigningCertificate($documentNo);
    }

    /**
     * Session status retrieval and validation
     */
    public function session(SessionContract $session): SessionClient
    {
        return $this->clientFactory->createSessionClient($session);
    }
}
