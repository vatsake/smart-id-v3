<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Requests\Concerns\ToArray;
use Vatsake\SmartIdV3\Builders\Request\DeviceLinkSigningRequestBuilder;

class DeviceLinkSigningRequest
{
    use ToArray;

    public readonly string $signatureProtocol;
    public readonly array $signatureProtocolParameters;
    public readonly array $requestProperties;
    public readonly string $interactions;
    public readonly string $originalData;
    public readonly ?string $nonce;
    public readonly ?string $certificateLevel;
    public readonly ?string $initialCallbackUrl;

    // Not the best solution, but we don't want to send the original data to Smart ID API, but we need it for validation later
    protected array $excludedFields = [
        'originalData'
    ];

    public function __construct(array $data)
    {
        $this->signatureProtocol = $data['signatureProtocol'];
        $this->signatureProtocolParameters = $data['signatureProtocolParameters'];
        $this->requestProperties = $data['requestProperties'];
        $this->interactions = $data['interactions'];
        $this->originalData = $data['originalData'];
        $this->certificateLevel = $data['certificateLevel'] ?? null;
        $this->initialCallbackUrl = $data['initialCallbackUrl'] ?? null;
        $this->nonce = $data['nonce'] ?? null;
    }

    public static function builder(): DeviceLinkSigningRequestBuilder
    {
        return new DeviceLinkSigningRequestBuilder();
    }
}
