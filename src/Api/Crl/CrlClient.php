<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Api\Crl;

use Vatsake\SmartIdV3\Api\ApiClient;

class CrlClient extends ApiClient
{
    public function fetchUrl(string $url)
    {
        $response = $this->get($url);
        $crlDer = $response->getBody()->getContents();
        return $crlDer;
    }
}
