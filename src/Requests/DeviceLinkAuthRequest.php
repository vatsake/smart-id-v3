<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\DeviceLinkAuthRequestBuilder;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

class DeviceLinkAuthRequest implements DeviceLinkRequest
{
    use ToArray;

    public function __construct(
        public readonly string $signatureProtocol,
        public readonly array $signatureProtocolParameters,
        public readonly array $requestProperties,
        public readonly string $interactions,
        public readonly ?string $certificateLevel = null,
        public readonly ?string $initialCallbackUrl = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['signatureProtocol'],
            $data['signatureProtocolParameters'],
            $data['requestProperties'],
            $data['interactions'],
            $data['certificateLevel'] ?? null,
            $data['initialCallbackUrl'] ?? null
        );
    }

    public static function builder(): DeviceLinkAuthRequestBuilder
    {
        return new DeviceLinkAuthRequestBuilder();
    }

    public function getSignedData(): string
    {
        return $this->signatureProtocolParameters['rpChallenge'];
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
        return 'auth';
    }
}
