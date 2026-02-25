<?php

namespace Vatsake\SmartIdV3\Responses\Signature;

use Vatsake\SmartIdV3\Data\SignatureData;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;

abstract class BaseSignature implements SignatureContract
{
    public readonly string $value;
    public readonly FlowType $flowType;
    public readonly SignatureAlgorithm $signatureAlgorithm;
    public readonly HashAlgorithm $signatureHashAlgorithm;
    public readonly string $signatureMaskGenAlgorithm;
    public readonly HashAlgorithm $signatureMaskGenHashAlgorithm;
    public readonly int $saltLength;
    public readonly string $trailerField;

    public function __construct(SignatureData $data)
    {
        $this->value = $data->value;
        $this->flowType = FlowType::tryFrom($data->flowType);
        $this->signatureAlgorithm = SignatureAlgorithm::tryFrom($data->signatureAlgorithm);
        $this->signatureHashAlgorithm = HashAlgorithm::tryFrom($data->signatureHashAlgorithm);
        $this->signatureMaskGenAlgorithm = $data->signatureMaskGenAlgorithm;
        $this->signatureMaskGenHashAlgorithm = HashAlgorithm::tryFrom($data->signatureMaskGenHashAlgorithm);
        $this->saltLength = $data->saltLength;
        $this->trailerField = $data->trailerField;
    }
}
