<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses\Signature;

use Vatsake\SmartIdV3\Enums\FlowType;

class CertificateChoiceSignature implements SignatureContract
{
    public readonly FlowType $flowType;

    public function __construct(
        string $flowType,
    ) {
        $this->flowType = FlowType::tryFrom($flowType);
    }
}
