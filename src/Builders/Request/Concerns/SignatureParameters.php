<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request\Concerns;

use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;

trait SignatureParameters
{
    protected function buildRawDigestSignatureParameters(): array
    {
        return [
            'signatureProtocol' => SignatureProtocol::RAW_DIGEST_SIGNATURE->value,
            'signatureProtocolParameters' => [
                'digest' => $this->digest,
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => $this->hashAlgorithm->value,
                ]
            ],
        ];
    }

    protected function buildAcspV2SignatureParameters(): array
    {
        return [
            'signatureProtocol' => SignatureProtocol::ACSP_V2->value,
            'signatureProtocolParameters' => [
                'rpChallenge' => $this->digest,
                'signatureAlgorithm' => SignatureAlgorithm::RSASSA_PSS->value,
                'signatureAlgorithmParameters' => [
                    'hashAlgorithm' => $this->hashAlgorithm->value,
                ]
            ],
        ];
    }
}
