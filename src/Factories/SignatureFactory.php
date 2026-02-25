<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Factories;

use Vatsake\SmartIdV3\Data\SignatureData;
use Vatsake\SmartIdV3\Responses\Signature\AcspV2Signature;
use Vatsake\SmartIdV3\Responses\Signature\CertificateChoiceSignature;
use Vatsake\SmartIdV3\Responses\Signature\RawDigestSignature;

class SignatureFactory
{
    public static function createAcspV2(array $data): AcspV2Signature
    {
        $signatureData = SignatureData::fromArray($data);
        return new AcspV2Signature(data: $signatureData, serverRandom: $data['serverRandom'], userChallenge: $data['userChallenge']);
    }

    public static function createRawDigest(array $data): RawDigestSignature
    {
        $signatureData = SignatureData::fromArray($data);
        return new RawDigestSignature($signatureData);
    }

    public static function createCertificateChoice(array $data): CertificateChoiceSignature
    {
        return new CertificateChoiceSignature(flowType: $data['flowType']);
    }
}
