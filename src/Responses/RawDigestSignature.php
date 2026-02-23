<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses;

use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;

class RawDigestSignature
{
    public readonly FlowType $flowType;
    public readonly SignatureAlgorithm $signatureAlgorithm;
    public readonly HashAlgorithm $signatureHashAlgorithm;
    public readonly HashAlgorithm $signatureMaskGenHashAlgorithm;

    public function __construct(
        public readonly string $value,
        string $flowType,
        string $signatureAlgorithm,
        string $signatureHashAlgorithm,
        public readonly string $signatureMaskGenAlgorithm,
        string $signatureMaskGenHashAlgorithm,
        public readonly int $signatureSaltLength,
        public readonly string $signatureTrailerField
    ) {
        $this->flowType = FlowType::tryFrom($flowType);
        $this->signatureAlgorithm = SignatureAlgorithm::tryFrom($signatureAlgorithm);
        $this->signatureHashAlgorithm = HashAlgorithm::tryFrom($signatureHashAlgorithm);
        $this->signatureMaskGenHashAlgorithm = HashAlgorithm::tryFrom($signatureMaskGenHashAlgorithm);
    }
}
