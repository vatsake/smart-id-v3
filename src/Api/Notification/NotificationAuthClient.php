<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\Notification;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Features\Notification\NotificationSession;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\Contracts\NotificationRequest;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Responses\NotificationResponse;
use Vatsake\SmartIdV3\Utils\VcCode;

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

    private function startAuth(NotificationRequest $req, string $endpoint): NotificationSession
    {
        return $this->requestSession($req, $this->config->getBaseUrl() . $endpoint, function (array $body) use ($req) {
            // Auth responses don't include vc, so we generate it from the signed data
            $body['vc'] = [
                'type' => 'numeric4',
                'value' => VcCode::fromRpChallenge($req->getSignedData()),
            ];

            $response = NotificationResponse::fromArray($body);
            return new NotificationSession($req, $response);
        });
    }
}
