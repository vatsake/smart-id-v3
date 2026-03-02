<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Requests\Concerns\ToArray;
use Vatsake\SmartIdV3\Builders\Request\DeviceLinkSigningRequestBuilder;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;

class DeviceLinkSigningRequest implements DeviceLinkRequest
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
        public readonly string $interactions,
        public readonly string $originalData,
        public readonly ?string $certificateLevel = null,
        public readonly ?string $initialCallbackUrl = null,
        public readonly ?string $nonce = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['signatureProtocol'],
            $data['signatureProtocolParameters'],
            $data['requestProperties'],
            $data['interactions'],
            $data['originalData'],
            $data['certificateLevel'] ?? null,
            $data['initialCallbackUrl'] ?? null,
            $data['nonce'] ?? null
        );
    }

    public static function builder(): DeviceLinkSigningRequestBuilder
    {
        return new DeviceLinkSigningRequestBuilder();
    }

    public function getSignedData(): string
    {
        return $this->originalData;
    }

    public function getSignatureProtocol(): string
    {
        return $this->signatureProtocol;
    }

    public function getInteractions(): string
    {
        return $this->interactions;
    }

    public function getInitialCallbackUrl(): string
    {
        return $this->initialCallbackUrl ?? '';
    }

    public function getSessionType(): string
    {
        return 'sign';
    }
}
