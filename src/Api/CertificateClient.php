<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api;

use Vatsake\SmartIdV3\Responses\Certificate;

class CertificateClient extends ApiClient
{
    public function getSigningCertificate(string $documentNo)
    {
        $params = [
            'relyingPartyUUID' => $this->config->getRelyingPartyUUID(),
            'relyingPartyName' => $this->config->getRelyingPartyName()
        ];
        $endpoint = "/signature/certificate/{$documentNo}";

        $response = $this->postJson($endpoint, $params);
        $body = json_decode($response->getBody()->getContents(), true);

        return new Certificate(
            $body['cert']['value'],
            $body['cert']['certificateLevel']
        );
    }
}
