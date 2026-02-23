<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\DeviceLink;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Enums\SessionType;
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;

class DeviceLinkSigningClient extends ApiClient
{
    /**
     * Start requesting signing certificate session with device link flow
     * Use notification flow's linked signing after this to start signing session
     */
    public function startAnonymousCertChoice(DeviceLinkCertChoiceRequest $req): DeviceLinkSession
    {
        $params = $this->buildRequestParams($req->toArray());

        $response = $this->postJson('/signature/certificate-choice/device-link/anonymous', $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new DeviceLinkSession(
            sessionId: $body['sessionID'],
            sessionToken: $body['sessionToken'],
            sessionSecret: $body['sessionSecret'],
            deviceLinkBase: $body['deviceLinkBase'],
            config: $this->config,
            sessionType: SessionType::CERT,
            signatureProtocol: '',
            digest: '',
            interactions: '',
            initialCallbackUrl: '',
            originalData: '',
        );
    }

    /**
     * Start ETSI signing session with device link flow
     */
    public function startEtsi(DeviceLinkSigningRequest $req, SemanticsIdentifier $identifier): DeviceLinkSession
    {
        $endpoint = "/signature/device-link/etsi/{$identifier->formattedString()}";
        return $this->startSigning($req, $endpoint);
    }

    /**
     * Start document-based signing session with device link flow
     */
    public function startDocument(DeviceLinkSigningRequest $req, string $documentNo): DeviceLinkSession
    {
        $endpoint = "/signature/device-link/document/{$documentNo}";
        return $this->startSigning($req, $endpoint);
    }

    private function startSigning(DeviceLinkSigningRequest $req, string $endpoint): DeviceLinkSession
    {
        $params = $this->buildRequestParams($req->toArray());

        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new DeviceLinkSession(
            sessionId: $body['sessionID'],
            sessionToken: $body['sessionToken'],
            sessionSecret: $body['sessionSecret'],
            deviceLinkBase: $body['deviceLinkBase'],
            sessionType: SessionType::SIGN,
            config: $this->config,
            originalData: $req->originalData,
            signatureProtocol: $req->signatureProtocol,
            digest: $req->signatureProtocolParameters['digest'],
            interactions: $req->interactions,
            initialCallbackUrl: $req->initialCallbackUrl ?? '',
        );
    }
}
