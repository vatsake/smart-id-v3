<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Builders\Request\Concerns\Nonce;
use Vatsake\SmartIdV3\Builders\Request\Concerns\OptionalFields;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;

class NotificationCertChoiceRequestBuilder extends RequestBuilder
{
    use Nonce;
    use OptionalFields;

    protected function validate(): void
    {
        $this->validateMandatoryParameters();
        $this->validateNonce();
    }

    protected function mandatoryParameters(): array
    {
        return [];
    }

    public function build(): NotificationCertChoiceRequest
    {
        $this->validate();

        $data = [
            'requestProperties' => $this->requestProperties,
        ];

        $data = $this->addOptionalFields($data, ['nonce', 'certificateLevel']);

        return new NotificationCertChoiceRequest($data);
    }
}
