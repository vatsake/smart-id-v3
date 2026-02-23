<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses\Ocsp;

use Vatsake\SmartIdV3\ASN1\OcspResponse as OcspResponseASN1;
use Vatsake\SmartIdV3\ASN1\OcspBasicResponse;
use Vatsake\SmartIdV3\Exceptions\OcspException;
use Vatsake\SmartIdV3\Utils\Asn1Helper;

/**
 * Parses OCSP response data.
 * Validation is done separately in RevocationValidator.
 */
class OcspResponse
{
    private const BASIC_RESPONSE_OID = '1.3.6.1.5.5.7.48.1.1';

    private array $outerResponse;
    private array $basicResponse;

    public function __construct(string $derContent)
    {
        $this->outerResponse = Asn1Helper::decode($derContent, OcspResponseASN1::MAP);
        $this->validateResponseStructure();
        $this->basicResponse = Asn1Helper::decode($this->outerResponse['responseBytes']['response'], OcspBasicResponse::MAP);
    }

    private function validateResponseStructure(): void
    {
        $status = $this->outerResponse['responseStatus'];
        $responseType = $this->outerResponse['responseBytes']['responseType'];

        if ($status !== 'successful') {
            throw new OcspException("OCSP response status is \"{$status}\"");
        }

        if ($responseType !== self::BASIC_RESPONSE_OID) {
            throw new OcspException("Response type {$responseType} not supported");
        }
    }

    public function getOuterResponse(): array
    {
        return $this->outerResponse;
    }

    public function getBasicResponse(): array
    {
        return $this->basicResponse;
    }

    public function getResponderCertificate(): string
    {
        return $this->basicResponse['certs'][0]->element;
    }

    public function getSignature(): string
    {
        return substr($this->basicResponse['signature'], 1); // Trim leading 0x00 byte
    }

    public function getSignatureAlgorithm(): string
    {
        $alg = strtolower($this->basicResponse['signatureAlgorithm']['algorithm']);

        if (preg_match('/sha(1|224|256|384|512)/', $alg, $matches)) {
            return 'sha' . $matches[1];
        }

        if (str_contains($alg, 'md5')) {
            return 'md5';
        }

        throw new OcspException("Unsupported signature algorithm: {$alg}");
    }

    public function getThisUpdate(): int
    {
        return strtotime($this->basicResponse['tbsResponseData']['responses'][0]['thisUpdate']);
    }

    public function getNextUpdate(): ?int
    {
        if (array_key_exists('nextUpdate', $this->basicResponse['tbsResponseData']['responses'][0])) {
            return strtotime($this->basicResponse['tbsResponseData']['responses'][0]['nextUpdate']);
        }
        return null;
    }

    public function getCertificateStatus(): string
    {
        return array_key_first($this->basicResponse['tbsResponseData']['responses'][0]['certStatus']);
    }

    public function getTbsResponseData(): array
    {
        return $this->basicResponse['tbsResponseData'];
    }
}
