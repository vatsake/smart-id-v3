<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\DeviceLinkCertChoiceRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

class DeviceLinkCertChoiceRequest
{
    use ToArray;

    public readonly array $requestProperties;
    public readonly ?string $nonce;
    public readonly ?string $certificateLevel;
    public readonly ?string $initialCallbackUrl;

    public function __construct(array $data)
    {
        $this->requestProperties = $data['requestProperties'];
        $this->certificateLevel = $data['certificateLevel'] ?? null;
        $this->initialCallbackUrl = $data['initialCallbackUrl'] ?? null;
        $this->nonce = $data['nonce'] ?? null;
    }

    public static function builder(): DeviceLinkCertChoiceRequestBuilder
    {
        return new DeviceLinkCertChoiceRequestBuilder();
    }
}
