<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\DeviceLinkCertChoiceRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;
use Vatsake\SmartIdV3\Requests\Contracts\DeviceLinkRequest;

class DeviceLinkCertChoiceRequest implements DeviceLinkRequest
{
    use ToArray;

    public function __construct(
        public readonly array $requestProperties,
        public readonly ?string $nonce = null,
        public readonly ?string $certificateLevel = null,
        public readonly ?string $initialCallbackUrl = null
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['requestProperties'],
            $data['nonce'] ?? null,
            $data['certificateLevel'] ?? null,
            $data['initialCallbackUrl'] ?? null
        );
    }

    public static function builder(): DeviceLinkCertChoiceRequestBuilder
    {
        return new DeviceLinkCertChoiceRequestBuilder();
    }

    public function getSignatureProtocol(): string
    {
        return '';
    }

    public function getInteractions(): string
    {
        return '';
    }

    public function getSignedData(): string
    {
        return '';
    }


    public function getInitialCallbackUrl(): string
    {
        return $this->initialCallbackUrl ?? '';
    }

    public function getSessionType(): string
    {
        return 'cert';
    }
}
