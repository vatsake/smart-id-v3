<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\ASN1;

use phpseclib3\File\ASN1;

abstract class QcStatements
{
    public const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'min' => 1,
        'max' => -1,
        'children' => [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'statementId' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
                'statementInfo' => [
                    'type' => ASN1::TYPE_SEQUENCE,
                    'optional' => true,
                    'children' => [
                        'semanticsIdentifier' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER, 'optional' => true],
                        'nameRegistrationAuthorities' => [
                            'type' => ASN1::TYPE_SEQUENCE,
                            'min' => 1,
                            'max' => -1,
                            'optional' => true,
                            'children' => [
                                'type' => ASN1::TYPE_ANY // Not needed
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ];
}
