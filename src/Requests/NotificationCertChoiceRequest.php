<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests;

use Vatsake\SmartIdV3\Builders\Request\NotificationCertChoiceRequestBuilder;
use Vatsake\SmartIdV3\Requests\Concerns\ToArray;

class NotificationCertChoiceRequest
{
    use ToArray;

    public readonly array $requestProperties;
    public readonly ?string $nonce;
    public readonly ?string $certificateLevel;

    public function __construct(array $data)
    {
        $this->requestProperties = $data['requestProperties'];
        $this->certificateLevel = $data['certificateLevel'] ?? null;
        $this->nonce = $data['nonce'] ?? null;
    }

    public static function builder(): NotificationCertChoiceRequestBuilder
    {
        return new NotificationCertChoiceRequestBuilder();
    }
}
