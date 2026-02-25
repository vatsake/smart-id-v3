<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\Notification;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Features\Notification\NotificationSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;

class NotificationSigningClient extends ApiClient
{
    /**
     * Start requesting signing certificate session with notification flow
     */
    public function startCertChoice(NotificationCertChoiceRequest $req, SemanticsIdentifier $identifier): NotificationSession
    {
        $params = $this->buildRequestParams($req->toArray());

        $endpoint = "/signature/certificate-choice/notification/etsi/{$identifier->formattedString()}";
        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new NotificationSession($body['sessionID']);
    }

    /**
     * Start signing session after DEVICE LINK based certificate choice with notification flow
     */
    public function startLinkedSigning(LinkedRequest $req, string $documentNo): NotificationSession
    {
        $params = $this->buildRequestParams($req->toArray());

        $endpoint = "/signature/notification/linked/{$documentNo}";
        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);
        return new NotificationSession(
            $body['sessionID'],
            $req->originalData,
            '',
            $req->initialCallbackUrl ?? ''
        );
    }

    /**
     * Start ETSI signing session with notification flow
     */
    public function startEtsi(NotificationSigningRequest $req, SemanticsIdentifier $identifier): NotificationSession
    {
        $endpoint = "/signature/notification/etsi/{$identifier->formattedString()}";
        return $this->startSigning($req, $endpoint);
    }

    /**
     * Start document-based signing with notification flow
     */
    public function startDocument(NotificationSigningRequest $req, string $documentNo): NotificationSession
    {
        $endpoint = "/signature/notification/document/{$documentNo}";
        return $this->startSigning($req, $endpoint);
    }

    private function startSigning(NotificationSigningRequest $req, string $endpoint): NotificationSession
    {
        $params = $this->buildRequestParams($req->toArray());

        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new NotificationSession(
            $body['sessionID'],
            $req->originalData,
            $req->interactions,
        );
    }
}
