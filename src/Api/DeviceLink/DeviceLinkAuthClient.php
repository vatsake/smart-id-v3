<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\DeviceLink;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Enums\SessionType;
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;

class DeviceLinkAuthClient extends ApiClient
{
    /**
     * Start anonymous authentication session with device link flow
     */
    public function startAnonymous(DeviceLinkAuthRequest $req): DeviceLinkSession
    {
        return $this->startAuth($req, '/authentication/device-link/anonymous');
    }

    /**
     * Start ETSI authentication session with device link flow
     */
    public function startEtsi(DeviceLinkAuthRequest $req, SemanticsIdentifier $identifier): DeviceLinkSession
    {
        $endpoint = "/authentication/device-link/etsi/{$identifier->formattedString()}";
        return $this->startAuth($req, $endpoint);
    }

    /**
     * Start document-based authentication session with device link flow.
     *
     */
    public function startDocument(DeviceLinkAuthRequest $req, string $documentNo): DeviceLinkSession
    {
        $endpoint = "/authentication/device-link/document/{$documentNo}";
        return $this->startAuth($req, $endpoint);
    }

    private function startAuth(DeviceLinkAuthRequest $req, string $endpoint): DeviceLinkSession
    {
        $params = $this->buildRequestParams($req->toArray());

        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new DeviceLinkSession(
            sessionId: $body['sessionID'],
            sessionToken: $body['sessionToken'],
            sessionSecret: $body['sessionSecret'],
            deviceLinkBase: $body['deviceLinkBase'],
            sessionType: SessionType::AUTH,
            config: $this->config,
            signatureProtocol: $req->signatureProtocol,
            digest: $req->signatureProtocolParameters['rpChallenge'],
            originalData: $req->signatureProtocolParameters['rpChallenge'],
            interactions: $req->interactions,
            initialCallbackUrl: $req->initialCallbackUrl ?? '',
        );
    }
}
