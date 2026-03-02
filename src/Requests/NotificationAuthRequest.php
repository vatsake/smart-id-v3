<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\NotificationAuthRequestBuilder;
use Vatsake\SmartIdV3\Requests\Contracts\NotificationRequest;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

class NotificationAuthRequest implements NotificationRequest
{
    use ToArray;

    public function __construct(
        public readonly string $signatureProtocol,
        public readonly array $signatureProtocolParameters,
        public readonly array $requestProperties,
        public readonly string $interactions,
        public readonly string $vcType,
        public readonly ?string $certificateLevel = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['signatureProtocol'],
            $data['signatureProtocolParameters'],
            $data['requestProperties'],
            $data['interactions'],
            $data['vcType'],
            $data['certificateLevel'] ?? null
        );
    }

    public static function builder(): NotificationAuthRequestBuilder
    {
        return new NotificationAuthRequestBuilder();
    }

    public function getSignedData(): string
    {
        return $this->signatureProtocolParameters['rpChallenge'];
    }

    public function getInteractions(): string
    {
        return $this->interactions;
    }
}
