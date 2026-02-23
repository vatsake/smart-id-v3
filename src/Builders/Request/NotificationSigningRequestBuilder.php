<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Builders\Request\Concerns\Interactions;
use Vatsake\SmartIdV3\Builders\Request\Concerns\Nonce;
use Vatsake\SmartIdV3\Builders\Request\Concerns\OptionalFields;
use Vatsake\SmartIdV3\Builders\Request\Concerns\SignatureParameters;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;

class NotificationSigningRequestBuilder extends RequestBuilder
{
    use Nonce;
    use Interactions;
    use SignatureParameters;
    use OptionalFields;

    protected ?string $digest = null;
    protected ?string $originalData = null;
    protected ?HashAlgorithm $hashAlgorithm = null;

    public function withData(string $rawData, HashAlgorithm $hashAlg): self
    {
        // We need the original data for validation later
        $this->originalData = $rawData;
        $this->digest = base64_encode(hash($hashAlg->getName(), $rawData, true));
        $this->hashAlgorithm = $hashAlg;
        return $this;
    }

    protected function mandatoryParameters(): array
    {
        return ['digest', 'hashAlgorithm'];
    }

    protected function validate(): void
    {
        $this->validateMandatoryParameters();
        $this->validateInteractions();
        $this->validateNonce();
    }

    /**
     * Mandatory params:
     * - digest - use withData()
     * - interactions - use withInteractions()
     */
    public function build(): NotificationSigningRequest
    {
        $this->validate();

        $data = array_merge(
            $this->buildRawDigestSignatureParameters(),
            [
                'interactions' => base64_encode(json_encode($this->interactions)),
                'requestProperties' => $this->requestProperties,
                'originalData' => $this->originalData
            ]
        );

        return new NotificationSigningRequest(
            $this->addOptionalFields($data, [
                'nonce' => 'nonce',
                'certificateLevel' => 'certificateLevel',
            ])
        );
    }
}
