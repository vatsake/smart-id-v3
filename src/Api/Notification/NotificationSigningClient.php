<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\Notification;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Features\DeviceLink\DeviceLinkSession;
use Vatsake\SmartIdV3\Features\DeviceLink\LinkedSession;
use Vatsake\SmartIdV3\Features\Notification\NotificationSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\Contracts\NotificationRequest;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Responses\LinkedResponse;
use Vatsake\SmartIdV3\Responses\NotificationResponse;

class NotificationSigningClient extends ApiClient
{
    /**
     * Start requesting signing certificate session with notification flow
     */
    public function startCertChoice(NotificationCertChoiceRequest $req, SemanticsIdentifier $identifier): NotificationSession
    {
        $endpoint = "/signature/certificate-choice/notification/etsi/{$identifier->formattedString()}";
        return $this->sendSignRequest($req, $endpoint);
    }

    /**
     * Start signing session after DEVICE LINK based certificate choice with notification flow
     */
    public function startLinkedSigning(LinkedRequest $req, string $documentNo): LinkedSession
    {
        $endpoint = "/signature/notification/linked/{$documentNo}";
        return $this->requestSession($req, $this->config->getBaseUrl() . $endpoint, function (array $body) use ($req) {
            $response = LinkedResponse::fromArray($body);
            return new LinkedSession($req, $response);
        });
    }

    /**
     * Start ETSI signing session with notification flow
     */
    public function startEtsi(NotificationSigningRequest $req, SemanticsIdentifier $identifier): NotificationSession
    {
        $endpoint = "/signature/notification/etsi/{$identifier->formattedString()}";
        return $this->sendSignRequest($req, $endpoint);
    }

    /**
     * Start document-based signing with notification flow
     */
    public function startDocument(NotificationSigningRequest $req, string $documentNo): NotificationSession
    {
        $endpoint = "/signature/notification/document/{$documentNo}";
        return $this->sendSignRequest($req, $endpoint);
    }

    private function sendSignRequest(NotificationRequest $req, string $endpoint): NotificationSession
    {
        return $this->requestSession($req, $this->config->getBaseUrl() . $endpoint, function (array $body) use ($req) {
            $response = NotificationResponse::fromArray($body);
            return new NotificationSession($req, $response);
        });
    }
}
