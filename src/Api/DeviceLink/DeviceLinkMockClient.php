<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\DeviceLink;

use Http\Discovery\Psr18Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Requests\MockRequest;

class DeviceLinkMockClient extends ApiClient
{
    protected Psr18Client $client;
    private ?LoggerInterface $logger;

    public function __construct(protected SmartIdConfig $config)
    {
        parent::__construct($config);
        $this->client = new Psr18Client($config->getHttpClient());
        $this->logger = $config->getLogger();
    }

    /**
     * Mocks the mobile device in demo environment.
     */
    public function start(MockRequest $mockRequest): void
    {
        $params = $mockRequest->toArray();
        $this->postMockJson('https://sid.demo.sk.ee/mock/device-link', $params);
    }

    private function postMockJson(string $url, ?array $payload = null, array $queryParams = []): ResponseInterface
    {
        $request = $this->client->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json');

        if ($payload !== null) {
            $body = $this->client->createStream(json_encode($payload));
            $request = $request->withBody($body);
            $this->logger?->debug('POST request', [
                'url' => $url,
                'payload' => $payload,
                'queryParams' => $queryParams,
            ]);
        }

        return $this->sendRequest($request);
    }
}
