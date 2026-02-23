<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api;

use Http\Discovery\Psr18Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\BadRequestException;
use Vatsake\SmartIdV3\Exceptions\ClientTooOldException;
use Vatsake\SmartIdV3\Exceptions\ForbiddenException;
use Vatsake\SmartIdV3\Exceptions\HttpException;
use Vatsake\SmartIdV3\Exceptions\MaintenanceException;
use Vatsake\SmartIdV3\Exceptions\UnauthorizedException;
use Vatsake\SmartIdV3\Exceptions\UserNotFoundException;

abstract class ApiClient
{
    private const USER_AGENT = 'smart-id-php-client/0.9.0';

    private Psr18Client $client;
    private ?LoggerInterface $logger;

    public function __construct(protected SmartIdConfig $config)
    {
        $this->client = new Psr18Client($config->getHttpClient());
        $this->logger = $config->getLogger();
    }

    /**
     * Send a POST request with JSON payload
     */
    protected function postJson(string $endpoint, ?array $payload = null, array $queryParams = []): ResponseInterface
    {
        $url = $this->buildUrl($endpoint, $queryParams);
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

    /**
     * Send a GET request
     */
    protected function getJson(string $endpoint, array $queryParams = []): ResponseInterface
    {
        $url = $this->buildUrl($endpoint, $queryParams);
        $request = $this->client->createRequest('GET', $url);

        $this->logger?->debug('GET request', [
            'url' => $url,
            'queryParams' => $queryParams,
        ]);

        return $this->sendRequest($request);
    }

    /**
     * Send a binary request (for OCSP and similar)
     */
    protected function postBinary(string $url, string $contentType, string $body): ResponseInterface
    {
        $request = $this->client->createRequest('POST', $url)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Accept', 'application/ocsp-response');

        $this->logger?->debug('POST binary request', [
            'url' => $url,
        ]);

        $stream = $this->client->createStream($body);
        $request = $request->withBody($stream);

        return $this->sendRequest($request);
    }

    private function buildUrl(string $endpoint, array $queryParams = []): string
    {
        $url = $this->config->getBaseUrl() . $endpoint;

        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    protected function buildRequestParams(array $requestData): array
    {
        return array_merge($requestData, [
            'relyingPartyUUID' => $this->config->getRelyingPartyUUID(),
            'relyingPartyName' => $this->config->getRelyingPartyName()
        ]);
    }

    protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $request->withHeader('User-Agent', self::USER_AGENT);
        $response = $this->client->sendRequest($request);

        $isBinary = str_contains($response->getHeaderLine('Content-Type'), 'application/ocsp-response');

        $this->logger?->debug('Received response', [
            'url' => $request->getRequestTarget(),
            'status_code' => $response->getStatusCode(),
            'response_body' => $isBinary ? '[binary data]' : (string) $response->getBody(),
        ]);
        $response->getBody()->rewind();
        $this->validateResponse((string) $request->getUri(), $response);

        return $response;
    }

    private function validateResponse(string $endpoint, ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 200) {
            return;
        }

        $body = json_decode($response->getBody()->getContents(), true);
        $response->getBody()->rewind();

        match ($statusCode) {
            400 => throw new BadRequestException($endpoint, $body),
            401 => throw new UnauthorizedException($endpoint),
            403 => throw new ForbiddenException($endpoint),
            404 => throw new UserNotFoundException($endpoint),
            480 => throw new ClientTooOldException($endpoint),
            580 => throw new MaintenanceException($endpoint),
            default => throw new HttpException($endpoint, $statusCode),
        };
    }
}
