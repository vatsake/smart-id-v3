<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\AuthRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

class AuthRequest
{
    use ToArray;

    public readonly string $signatureProtocol;
    public readonly array $signatureProtocolParameters;
    public readonly array $requestProperties;
    public readonly string $interactions;
    public readonly ?string $certificateLevel;
    public readonly ?string $initialCallbackUrl;

    public function __construct(array $data)
    {
        $this->signatureProtocol = $data['signatureProtocol'];
        $this->signatureProtocolParameters = $data['signatureProtocolParameters'];
        $this->requestProperties = $data['requestProperties'];
        $this->interactions = $data['interactions'];
        $this->certificateLevel = $data['certificateLevel'] ?? null;
        $this->initialCallbackUrl = $data['initialCallbackUrl'] ?? null;
    }

    public static function builder(): AuthRequestBuilder
    {
        return new AuthRequestBuilder();
    }
}
