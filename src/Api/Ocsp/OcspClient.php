<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\Ocsp;

use Vatsake\SmartIdV3\Api\ApiClient;
use Vatsake\SmartIdV3\Requests\Ocsp\OcspRequest;
use Vatsake\SmartIdV3\Responses\Ocsp\OcspResponse;

class OcspClient extends ApiClient
{
    public function sendOcspRequest(OcspRequest $ocspRequest, string $url): OcspResponse
    {
        $response = $this->postBinary($url, 'application/ocsp-request', $ocspRequest->getBody());
        return new OcspResponse($response->getBody()->getContents());
    }
}
