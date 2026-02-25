<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Builders\Request\Concerns\Interactions;
use Vatsake\SmartIdV3\Builders\Request\Concerns\OptionalFields;
use Vatsake\SmartIdV3\Builders\Request\Concerns\SignatureParameters;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;

class NotificationAuthRequestBuilder extends RequestBuilder
{
    use Interactions;
    use SignatureParameters;
    use OptionalFields;

    protected ?string $digest = null;
    protected ?HashAlgorithm $hashAlgorithm = null;

    public function withRpChallenge(string $rpChallenge, HashAlgorithm $hashAlg): self
    {
        $this->digest = $rpChallenge;
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
    }

    /**
     * Mandatory params:
     * - digest - use withRpChallenge()
     * - interactions - use withInteractions()
     */
    public function build()
    {
        $this->validate();

        $data = array_merge(
            $this->buildAcspV2SignatureParameters(),
            [
                'interactions' => base64_encode(json_encode($this->interactions)),
                'requestProperties' => $this->requestProperties
            ]
        );

        $data = $this->addOptionalFields($data, ['certificateLevel']);

        return new NotificationAuthRequest($data);
    }
}
