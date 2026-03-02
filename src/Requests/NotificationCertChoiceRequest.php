<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\NotificationCertChoiceRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;
use Vatsake\SmartIdV3\Requests\Contracts\NotificationRequest;

class NotificationCertChoiceRequest implements NotificationRequest
{
    use ToArray;

    public function __construct(
        public readonly array $requestProperties,
        public readonly ?string $certificateLevel = null,
        public readonly ?string $nonce = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['requestProperties'],
            $data['certificateLevel'] ?? null,
            $data['nonce'] ?? null
        );
    }

    public static function builder(): NotificationCertChoiceRequestBuilder
    {
        return new NotificationCertChoiceRequestBuilder();
    }

    public function getSignedData(): string
    {
        return '';
    }

    public function getInteractions(): string
    {
        return '';
    }
}
