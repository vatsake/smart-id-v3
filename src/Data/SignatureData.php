<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Data;

class SignatureData
{
    public function __construct(
        public readonly string $value,
        public readonly string $flowType,
        public readonly string $signatureAlgorithm,
        public readonly string $signatureHashAlgorithm,
        public readonly string $signatureMaskGenAlgorithm,
        public readonly string $signatureMaskGenHashAlgorithm,
        public readonly int $saltLength,
        public readonly string $trailerField,
    ) {}

    public static function fromArray(array $data): self
    {
        $params = $data['signatureAlgorithmParameters'];
        $maskGenAlg = $params['maskGenAlgorithm'];
        $maskGenParams = $maskGenAlg['parameters'];

        return new self(
            value: $data['value'],
            flowType: $data['flowType'],
            signatureAlgorithm: $data['signatureAlgorithm'],
            signatureHashAlgorithm: $params['hashAlgorithm'],
            signatureMaskGenAlgorithm: $maskGenAlg['algorithm'],
            signatureMaskGenHashAlgorithm: $maskGenParams['hashAlgorithm'],
            saltLength: $params['saltLength'],
            trailerField: $params['trailerField'],
        );
    }
}
