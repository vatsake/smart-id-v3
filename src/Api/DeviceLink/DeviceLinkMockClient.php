<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\DeviceLink;

use Http\Discovery\Psr18Client;
use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Requests\MockRequest;

class DeviceLinkMockClient extends ApiClient
{
    protected Psr18Client $client;

    public function __construct(protected SmartIdConfig $config)
    {
        parent::__construct($config);
        $this->client = new Psr18Client($config->getHttpClient());
    }

    /**
     * Mocks the mobile device in demo environment.
     */
    public function start(MockRequest $mockRequest): void
    {
        $params = $mockRequest->toArray();
        $this->postJson('https://sid.demo.sk.ee/mock/device-link', $params);
    }
}
