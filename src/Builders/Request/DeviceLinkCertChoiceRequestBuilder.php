<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Builders\Request\Concerns\InitialCallbackUrl;
use Vatsake\SmartIdV3\Builders\Request\Concerns\Nonce;
use Vatsake\SmartIdV3\Builders\Request\Concerns\OptionalFields;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;

class DeviceLinkCertChoiceRequestBuilder extends RequestBuilder
{
    use Nonce;
    use InitialCallbackUrl;
    use OptionalFields;

    protected function validate(): void
    {
        $this->validateMandatoryParameters();
        $this->validateInitialCallbackUrl();
        $this->validateNonce();
    }

    protected function mandatoryParameters(): array
    {
        return [];
    }

    public function build(): DeviceLinkCertChoiceRequest
    {
        $this->validate();

        $data = [
            'requestProperties' => $this->requestProperties,
        ];

        $data = $this->addOptionalFields($data, [
            'nonce',
            'certificateLevel',
            'initialCallbackUrl',
        ]);

        return new DeviceLinkCertChoiceRequest($data);
    }
}
