<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Builders\Request\Concerns\InitialCallbackUrl;
use Vatsake\SmartIdV3\Builders\Request\Concerns\Interactions;
use Vatsake\SmartIdV3\Builders\Request\Concerns\Nonce;
use Vatsake\SmartIdV3\Builders\Request\Concerns\OptionalFields;
use Vatsake\SmartIdV3\Builders\Request\Concerns\SignableData;
use Vatsake\SmartIdV3\Builders\Request\Concerns\SignatureParameters;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\LinkedRequest;

class LinkedRequestBuilder extends RequestBuilder
{
    use Nonce;
    use Interactions;
    use InitialCallbackUrl;
    use SignatureParameters;
    use OptionalFields;

    protected ?string $linkedSessionId = null;

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

    public function withLinkedSessionId(string $sessionId): self
    {
        $this->linkedSessionId = $sessionId;
        return $this;
    }

    protected function mandatoryParameters(): array
    {
        return ['digest', 'hashAlgorithm', 'linkedSessionId', 'interactions'];
    }

    protected function validate(): void
    {
        $this->validateMandatoryParameters();
        $this->validateNonce();
        $this->validateInteractions();
        $this->validateInitialCallbackUrl();
    }

    /**
     * Mandatory params:
     * - digest - use withData()
     * - interactions - use withInteractions()
     * - linkedSessionId - use withLinkedSessionId()
     */
    public function build()
    {
        $this->validate();

        $data = array_merge(
            $this->buildRawDigestSignatureParameters(),
            [
                'interactions' => base64_encode(json_encode($this->interactions)),
                'linkedSessionId' => $this->linkedSessionId,
                'requestProperties' => $this->requestProperties,
                'originalData' => $this->originalData
            ]
        );

        $data = $this->addOptionalFields($data, [
            'nonce',
            'certificateLevel',
            'initialCallbackUrl',
        ]);

        return new LinkedRequest($data);
    }
}
