<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Factories;

use Vatsake\SmartIdV3\Responses\AcspV2Signature;
use Vatsake\SmartIdV3\Responses\CertificateChoiceSignature;
use Vatsake\SmartIdV3\Responses\RawDigestSignature;

class SignatureFactory
{
    public static function createAcspV2(array $data): AcspV2Signature
    {
        return new AcspV2Signature(
            value: $data['value'],
            serverRandom: $data['serverRandom'],
            userChallenge: $data['userChallenge'],
            flowType: $data['flowType'],
            signatureAlgorithm: $data['signatureAlgorithm'],
            signatureHashAlgorithm: $data['signatureAlgorithmParameters']['hashAlgorithm'],
            signatureMaskGenAlgorithm: $data['signatureAlgorithmParameters']['maskGenAlgorithm']['algorithm'],
            signatureMaskGenHashAlgorithm: $data['signatureAlgorithmParameters']['maskGenAlgorithm']['parameters']['hashAlgorithm'],
            signatureSaltLength: $data['signatureAlgorithmParameters']['saltLength'],
            signatureTrailerField: $data['signatureAlgorithmParameters']['trailerField']
        );
    }

    public static function createRawDigest(array $data): RawDigestSignature
    {
        return new RawDigestSignature(
            value: $data['value'],
            flowType: $data['flowType'],
            signatureAlgorithm: $data['signatureAlgorithm'],
            signatureHashAlgorithm: $data['signatureAlgorithmParameters']['hashAlgorithm'],
            signatureMaskGenAlgorithm: $data['signatureAlgorithmParameters']['maskGenAlgorithm']['algorithm'],
            signatureMaskGenHashAlgorithm: $data['signatureAlgorithmParameters']['maskGenAlgorithm']['parameters']['hashAlgorithm'],
            signatureSaltLength: $data['signatureAlgorithmParameters']['saltLength'],
            signatureTrailerField: $data['signatureAlgorithmParameters']['trailerField']
        );
    }

    public static function createCertificateChoice(array $data): CertificateChoiceSignature
    {
        return new CertificateChoiceSignature(
            flowType: $data['flowType']
        );
    }
}
