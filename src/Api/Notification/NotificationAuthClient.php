<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\Notification;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Features\Notification\NotificationSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;

class NotificationAuthClient extends ApiClient
{
    /**
     * Start ETSI authentication session with notification flow
     */
    public function startEtsi(NotificationAuthRequest $req, SemanticsIdentifier $identifier): NotificationSession
    {
        $endpoint = "/authentication/notification/etsi/{$identifier->formattedString()}";
        return $this->startAuth($req, $endpoint);
    }

    /**
     * Start document-based authentication session with notification flow
     */
    public function startDocument(NotificationAuthRequest $req, string $documentNo): NotificationSession
    {
        $endpoint = "/authentication/notification/document/{$documentNo}";
        return $this->startAuth($req, $endpoint);
    }

    private function startAuth(NotificationAuthRequest $req, string $endpoint): NotificationSession
    {
        $params = $this->buildRequestParams($req->toArray());
        $params['vcType'] = 'numeric4';

        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new NotificationSession(
            $body['sessionID'],
            $req->signatureProtocolParameters['rpChallenge'],
            $req->interactions,
            $req->initialCallbackUrl ?? ''
        );
    }
}
