<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\ASN1;

use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\AlgorithmIdentifier;
use phpseclib3\File\ASN1\Maps\Certificate;
use phpseclib3\File\ASN1\Maps\CertificateSerialNumber;
use phpseclib3\File\ASN1\Maps\Extensions;
use phpseclib3\File\ASN1\Maps\GeneralName;

abstract class OcspRequest
{
    public const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'tbsRequest' => [
                'type' => ASN1::TYPE_SEQUENCE,
                'children' => [
                    'version' => [
                        'type' => ASN1::TYPE_INTEGER,
                        'explicit' => true,
                        'constant' => 0,
                    ],
                    'requestorName' => [
                        ...GeneralName::MAP,
                        'explicit' => true,
                        'optional' => true,
                        'constant' => 1,
                    ],
                    'requestList' => [
                        'type' => ASN1::TYPE_SEQUENCE,
                        'min' => 1,
                        'max' => -1,
                        'children' => [
                            'type' => ASN1::TYPE_SEQUENCE,
                            'children' => [
                                'reqCert' => [
                                    'type' => ASN1::TYPE_SEQUENCE,
                                    'children' => [
                                        'hashAlgorithm' => AlgorithmIdentifier::MAP,
                                        'issuerNameHash' => ['type' => ASN1::TYPE_OCTET_STRING],
                                        'issuerKeyHash' => ['type' => ASN1::TYPE_OCTET_STRING],
                                        'serialNumber' => CertificateSerialNumber::MAP
                                    ]
                                ],
                                'singleRequestExtensions' => [
                                    ...Extensions::MAP,
                                    'constant' => 0,
                                    'explicit' => true,
                                    'optional' => true,
                                ]
                            ]
                        ]
                    ],
                    'requestExtensions' => [
                        ...Extensions::MAP,
                        'explicit' => true,
                        'optional' => true,
                        'constant' => 2,
                    ]
                ]
            ],
            'optionalSignature' => [
                'type' => ASN1::TYPE_SEQUENCE,
                'children' => [
                    'signatureAlgorithm' => AlgorithmIdentifier::MAP,
                    'signature' => ['type' => ASN1::TYPE_BIT_STRING],
                    'certs' => [
                        'type' => ASN1::TYPE_SEQUENCE,
                        'explicit' => true,
                        'constant' => 0,
                        'optional' => true,
                        'min' => 1,
                        'max' => -1,
                        'children' => Certificate::MAP
                    ]
                ],
                'explicit' => true,
                'optional' => true,
                'constant' => 0,
            ],
        ]
    ];
}
