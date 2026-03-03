<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\LinkedRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;

class LinkedRequest implements DeviceLinkRequest
{
    use ToArray;

    // Not the best solution, but we don't want to send the original data to Smart ID API, but we need it for validation later
    protected array $excludedFields = [
        'originalData'
    ];

    public function __construct(
        public readonly string $signatureProtocol,
        public readonly array $signatureProtocolParameters,
        public readonly array $requestProperties,
        public readonly string $originalData,
        public readonly string $interactions,
        public readonly string $linkedSessionID,
        public readonly ?string $nonce = null,
        public readonly ?string $certificateLevel = null,
        public readonly ?string $initialCallbackUrl = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['signatureProtocol'],
            $data['signatureProtocolParameters'],
            $data['requestProperties'],
            $data['originalData'],
            $data['interactions'],
            $data['linkedSessionID'],
            $data['nonce'] ?? null,
            $data['certificateLevel'] ?? null,
            $data['initialCallbackUrl'] ?? null
        );
    }

    public static function builder(): LinkedRequestBuilder
    {
        return new LinkedRequestBuilder();
    }

    public function getSignatureProtocol(): string
    {
        return $this->signatureProtocol;
    }

    public function getInteractions(): string
    {
        return $this->interactions;
    }

    public function getSignedData(): string
    {
        return $this->originalData;
    }

    public function getSessionType(): string
    {
        return 'sign';
    }

    public function getInitialCallbackUrl(): string
    {
        return $this->initialCallbackUrl ?? '';
    }
}
