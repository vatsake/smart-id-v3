<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses\Signature;

use Vatsake\SmartIdV3\Data\SignatureData;

class AcspV2Signature extends BaseSignature
{
    public function __construct(
        SignatureData $data,
        public readonly string $serverRandom,
        public readonly string $userChallenge,
    ) {
        parent::__construct($data);
    }
}
