<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Responses;

use OpenSSLCertificate;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Utils\PemFormatter;

class Certificate
{
    private array $x509;
    public readonly CertificateLevel $certificateLevel;

    public function __construct(
        public readonly string $valueInBase64,
        string $certificateLevel
    ) {
        $this->certificateLevel = CertificateLevel::tryFrom($certificateLevel);
    }

    // Subject's given name in title case
    public function getSubjectGN(): string
    {
        return mb_convert_case(mb_strtolower($this->getX509()['subject']['GN']), MB_CASE_TITLE);
    }

    // Subject's surname in title case
    public function getSubjectSN(): string
    {
        return mb_convert_case(mb_strtolower($this->getX509()['subject']['SN']), MB_CASE_TITLE);
    }

    // Subject's name in title case
    public function getSubjectName(): string
    {
        return $this->getSubjectGN() . ' ' . $this->getSubjectSN();
    }

    public function getSubjectIdentifier(): SemanticsIdentifier
    {
        return SemanticsIdentifier::fromString($this->getX509()['subject']['serialNumber']);
    }

    public function getX509Resource(): OpenSSLCertificate|false
    {
        return openssl_x509_read(PemFormatter::addPemHeaders($this->valueInBase64));
    }

    /** @var array{name: string, subject: array{C: string, CN: string, SN: string, GN: string, serialNumber: string}, hash: string, issuer: array{C: string, O: string, organizationIdentifier: string, CN: string}, version: int, serialNumber: string, serialNumberHex: string, validFrom: string, validTo: string, validFrom_time_t: int, validTo_time_t: int, alias: string, signatureTypeSN: string, signatureTypeLN: string, signatureTypeNID: int, purposes: array, extensions: array} */
    public function getX509(): array
    {
        if (!isset($this->x509)) {
            $this->x509 = openssl_x509_parse(PemFormatter::addPemHeaders($this->valueInBase64));
        }
        return $this->x509;
    }
}
