<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request\Ocsp;

use phpseclib3\File\ASN1;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;
use Vatsake\SmartIdV3\ASN1\OcspRequest as OcspRequestASN1;
use Vatsake\SmartIdV3\Requests\Ocsp\OcspRequest;
use Vatsake\SmartIdV3\Utils\PemFormatter;

class OcspRequestBuilder
{
    protected ?string $subjectCertificate = null;
    protected ?string $issuerCertificate = null;

    public function withSubjectCertificate(string $certInBase64): self
    {
        $this->subjectCertificate = $certInBase64;
        return $this;
    }

    public function withIssuerCertificate(string $certInBase64): self
    {
        $this->issuerCertificate = $certInBase64;
        return $this;
    }

    public function build(): OcspRequest
    {
        $subject = openssl_x509_parse(PemFormatter::addPemHeaders($this->subjectCertificate));
        if ($subject === false) {
            throw new \RuntimeException('Failed to parse subject certificate');
        }

        $x509 = new X509();
        $x509->loadX509(PemFormatter::addPemHeaders($this->issuerCertificate));
        if ($x509->getCurrentCert() === false) {
            throw new \RuntimeException('Failed to parse issuer certificate');
        }

        $signerSn = $this->serialToNumber($subject['serialNumber']);
        $issuerName = $x509->getDN(X509::DN_ASN1);
        $issuerPubKey = $this->getPublicKey($x509);

        $req = [
            'tbsRequest' => [
                'version' => 0,
                'requestList' => [
                    [
                        'reqCert' => [
                            'hashAlgorithm' => [
                                'algorithm' => '1.3.14.3.2.26', // OID for SHA-1
                                'parameters' => null
                            ],
                            'issuerNameHash' => hash('sha1', $issuerName, true),
                            'issuerKeyHash' => hash('sha1', $issuerPubKey, true),
                            'serialNumber' => $signerSn
                        ]
                    ]
                ]
            ]
        ];

        $encodedData = ASN1::encodeDER($req, OcspRequestASN1::MAP);
        if ($encodedData === false) {
            throw new \RuntimeException('ASN1 mapping failed: ' . json_encode(OcspRequestASN1::MAP, JSON_PRETTY_PRINT));
        }

        return new OcspRequest($encodedData);
    }

    private function getPublicKey(X509 $x509): string
    {
        $publicKey = $x509->getPublicKey()->toString('PKCS8');
        $keyData = preg_replace(
            '/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/',
            '',
            $publicKey
        );

        $nodes = ASN1::decodeBER(base64_decode($keyData));

        $key = $nodes[0]['content'][1]['content'];

        // TAG is not hashed
        $key = substr($key, 1);

        return $key;
    }

    private function serialToNumber(string $input): string
    {
        $s = preg_replace('/\s+/', '', trim($input));
        if ($s === '') {
            return '0';
        }

        $isHex = false;
        if (preg_match('/^0x/i', $s)) {
            $s = substr($s, 2);
            $isHex = true;
        } elseif (preg_match('/[A-Fa-f]/', $s)) {
            $isHex = true;
        }

        if ($isHex) {
            $hex = ltrim($s, '0');
            if ($hex === '') {
                return '0';
            }
            $bi = new BigInteger($hex, 16);
            return $bi->toString(10);
        }

        if (!preg_match('/^\d+$/', $s)) {
            return '';
        }

        $s = ltrim($s, '0') ?: '0';
        return $s;
    }
}
