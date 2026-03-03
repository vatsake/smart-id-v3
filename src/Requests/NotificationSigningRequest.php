<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Requests\Concerns\ToArray;
use Vatsake\SmartIdV3\Builders\Request\NotificationSigningRequestBuilder;
use Vatsake\SmartIdV3\Requests\Contracts\NotificationRequest;

class NotificationSigningRequest implements NotificationRequest
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
        public readonly ?string $nonce = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['signatureProtocol'],
            $data['signatureProtocolParameters'],
            $data['requestProperties'],
            $data['interactions'],
            $data['originalData'],
            $data['certificateLevel'] ?? null,
            $data['nonce'] ?? null
        );
    }

    public static function builder(): NotificationSigningRequestBuilder
    {
        return new NotificationSigningRequestBuilder();
    }

    public function getSignedData(): string
    {
        return $this->originalData;
    }

    public function getInteractions(): string
    {
        return $this->interactions;
    }
}
