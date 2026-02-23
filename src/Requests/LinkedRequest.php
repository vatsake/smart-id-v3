<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\LinkedRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

class LinkedRequest
{
    use ToArray;

    public readonly array $signatureProtocolParameters;
    public readonly array $requestProperties;
    public readonly string $signatureProtocol;
    public readonly string $originalData;
    public readonly string $interactions;
    public readonly ?string $linkedSessionID;
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
        $this->linkedSessionID = $data['linkedSessionId'];
        $this->originalData = $data['originalData'];
        $this->interactions = $data['interactions'];
        $this->nonce = $data['nonce'] ?? null;
        $this->certificateLevel = $data['certificateLevel'] ?? null;
        $this->initialCallbackUrl = $data['initialCallbackUrl'] ?? null;
    }

    public static function builder(): LinkedRequestBuilder
    {
        return new LinkedRequestBuilder();
    }
}
