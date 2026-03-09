<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\DeviceLink;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkAuthRequest;
use Vatsake\SmartIdV3\Responses\DeviceLinkResponse;

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

    private function startAuth(DeviceLinkRequest $req, string $endpoint): DeviceLinkSession
    {
        return $this->requestSession($req, $endpoint, function (array $body) use ($req) {
            $response = DeviceLinkResponse::fromArray($body);
            return new DeviceLinkSession($req, $response, $this->config);
        });
    }
}
