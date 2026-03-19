<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\DeviceLink;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;
use Vatsake\SmartIdV3\Responses\DeviceLinkResponse;

class DeviceLinkSigningClient extends ApiClient
{
    /**
     * Start requesting signing certificate session with device link flow
     * Use notification flow's linked signing after this to start signing session
     */
    public function startAnonymousCertChoice(DeviceLinkCertChoiceRequest $req): DeviceLinkSession
    {
        $endpoint = '/signature/certificate-choice/device-link/anonymous';
        return $this->sendSignRequest($req, $endpoint);
    }

    /**
     * Start ETSI signing session with device link flow
     */
    public function startEtsi(DeviceLinkSigningRequest $req, SemanticsIdentifier $identifier): DeviceLinkSession
    {
        $endpoint = "/signature/device-link/etsi/{$identifier->formattedString()}";
        return $this->sendSignRequest($req, $endpoint);
    }

    /**
     * Start document-based signing session with device link flow
     */
    public function startDocument(DeviceLinkSigningRequest $req, string $documentNo): DeviceLinkSession
    {
        $endpoint = "/signature/device-link/document/{$documentNo}";
        return $this->sendSignRequest($req, $endpoint);
    }

    private function sendSignRequest(DeviceLinkRequest $req, string $endpoint): DeviceLinkSession
    {
        return $this->requestSession($req, $this->config->getBaseUrl() . $endpoint, function (array $body) use ($req) {
            $sessionResponse = DeviceLinkResponse::fromArray($body);
            return new DeviceLinkSession($req, $sessionResponse, $this->config);
        });
    }
}
