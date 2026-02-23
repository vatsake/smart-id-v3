<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Identity;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;

class SemanticsIdentifierTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $identifier = new SemanticsIdentifier(
            NaturalIdentityType::NATIONAL_PERSONAL_NUMBER,
            'EE',
            '40504040001'
        );

        $this->assertEquals(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER, $identifier->type);
        $this->assertEquals('EE', $identifier->countryCode);
        $this->assertEquals('40504040001', $identifier->identifier);
    }

    public function testFromStringParsesCorrectly(): void
    {
        $identifier = SemanticsIdentifier::fromString('PNOEE-40504040001');

        $this->assertEquals(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER, $identifier->type);
        $this->assertEquals('EE', $identifier->countryCode);
        $this->assertEquals('40504040001', $identifier->identifier);
    }

    public function testFromStringWithPassportType(): void
    {
        $identifier = SemanticsIdentifier::fromString('PASFR-N123456789');

        $this->assertEquals(NaturalIdentityType::PASSPORT, $identifier->type);
        $this->assertEquals('FR', $identifier->countryCode);
        $this->assertEquals('N123456789', $identifier->identifier);
    }

    public function testFromStringWithNationalIdType(): void
    {
        $identifier = SemanticsIdentifier::fromString('IDCDE-987654321');

        $this->assertEquals(NaturalIdentityType::NATIONAL_ID_NUMBER, $identifier->type);
        $this->assertEquals('DE', $identifier->countryCode);
        $this->assertEquals('987654321', $identifier->identifier);
    }

    public function testFromPersonalNumberCreatesCorrectIdentifier(): void
    {
        $identifier = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');

        $this->assertEquals(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER, $identifier->type);
        $this->assertEquals('EE', $identifier->countryCode);
        $this->assertEquals('40504040001', $identifier->identifier);
    }

    public function testFormattedStringReturnsCorrectFormat(): void
    {
        $identifier = new SemanticsIdentifier(
            NaturalIdentityType::NATIONAL_PERSONAL_NUMBER,
            'EE',
            '40504040001'
        );

        $this->assertEquals('PNOEE-40504040001', $identifier->formattedString());
    }

    public function testFromStringAndFormattedStringAreReversible(): void
    {
        $original = 'PNOEE-40504040001';
        $identifier = SemanticsIdentifier::fromString($original);
        $formatted = $identifier->formattedString();

        $this->assertEquals($original, $formatted);
    }
}
