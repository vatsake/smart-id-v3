<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Identity;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Identity\SemanticsIdentifierBuilder;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;

class SemanticsIdentifierBuilderTest extends TestCase
{
    public function testSuccessfulBuild(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('40504040001')
            ->build();

        $this->assertInstanceOf(SemanticsIdentifier::class, $identifier);
        $this->assertEquals(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER, $identifier->type);
        $this->assertEquals('EE', $identifier->countryCode);
        $this->assertEquals('40504040001', $identifier->identifier);
    }

    public function testCountryCodeConvertsToUppercase(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('lt')
            ->withIdentifier('35505250001')
            ->build();

        $this->assertEquals('LT', $identifier->countryCode);
    }

    public function testBuilderReturnsBuilderInstance(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $result = $builder->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER);
        $this->assertSame($builder, $result);
    }

    public function testMissingType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing mandatory parameter: type');

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withCountryCode('ee')
            ->withIdentifier('40504040001')
            ->build();
    }

    public function testMissingCountryCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing mandatory parameter: countryCode');

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withIdentifier('40504040001')
            ->build();
    }

    public function testMissingIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing mandatory parameter: identifier');

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->build();
    }

    public function testInvalidCountryCodeLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Country code must be exactly 2 characters');

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('eee')
            ->withIdentifier('40504040001')
            ->build();
    }

    public function testAllNaturalIdentityTypes(): void
    {
        foreach (NaturalIdentityType::cases() as $type) {
            $builder = new SemanticsIdentifierBuilder();
            $identifier = $builder
                ->withType($type)
                ->withCountryCode('ee')
                ->withIdentifier('40504040001')
                ->build();

            $this->assertEquals($type, $identifier->type);
        }
    }

    // ========== EDGE CASE TESTS ==========

    public function testIdentifierWithEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('')
            ->build();
    }

    public function testCountryCodeWithEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('')
            ->withIdentifier('40504040001')
            ->build();
    }

    public function testCountryCodeWithOneCharacter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Country code must be exactly 2 characters');

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('e')
            ->withIdentifier('40504040001')
            ->build();
    }

    public function testCountryCodeWithThreeCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Country code must be exactly 2 characters');

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('eee')
            ->withIdentifier('40504040001')
            ->build();
    }

    public function testCountryCodeWithWhitespace(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('e ')
            ->withIdentifier('40504040001')
            ->build();

        $this->assertEquals('E ', $identifier->countryCode);
    }

    public function testCountryCodeWithNumbers(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('12')
            ->withIdentifier('40504040001')
            ->build();

        $this->assertEquals('12', $identifier->countryCode);
    }

    public function testIdentifierWithVeryLongString(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $longIdentifier = str_repeat('9', 1000);

        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier($longIdentifier)
            ->build();

        $this->assertEquals($longIdentifier, $identifier->identifier);
    }

    public function testIdentifierWithSpecialCharacters(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('40504040001-ABC!@#')
            ->build();

        $this->assertEquals('40504040001-ABC!@#', $identifier->identifier);
    }

    public function testIdentifierWithUnicodeCharacters(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $unicodeIdentifier = '40504040001_ñäöü_中文';

        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier($unicodeIdentifier)
            ->build();

        $this->assertEquals($unicodeIdentifier, $identifier->identifier);
    }

    public function testCountryCodeWithDifferentLetters(): void
    {
        // Any 2-character code is allowed
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ZZ')
            ->withIdentifier('40504040001')
            ->build();

        $this->assertEquals('ZZ', $identifier->countryCode);
    }

    public function testBuilderReusability(): void
    {
        $builder = new SemanticsIdentifierBuilder();

        $identifier1 = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('40504040001')
            ->build();

        // Reset builder and reuse (note: actual behavior depends on implementation)
        $identifier2 = $builder
            ->withType(NaturalIdentityType::PASSPORT)
            ->withCountryCode('lt')
            ->withIdentifier('35505250001')
            ->build();

        $this->assertEquals('40504040001', $identifier1->identifier);
        $this->assertEquals('35505250001', $identifier2->identifier);
    }

    public function testCountryCodeCaseSensitivity(): void
    {
        $builderLower = new SemanticsIdentifierBuilder();
        $identifierLower = $builderLower
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('40504040001')
            ->build();

        $builderUpper = new SemanticsIdentifierBuilder();
        $identifierUpper = $builderUpper
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('EE')
            ->withIdentifier('40504040001')
            ->build();

        // Both should be uppercase
        $this->assertEquals('EE', $identifierLower->countryCode);
        $this->assertEquals('EE', $identifierUpper->countryCode);
    }

    public function testBuilderWithoutCallingBuild(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $builder = new SemanticsIdentifierBuilder();
        // Not calling build() should result in error when accessing properties
        // or build() must be called
        $builder->build();
    }

    public function testIdentifierWithLeadingTrailingWhitespace(): void
    {
        $builder = new SemanticsIdentifierBuilder();
        $identifier = $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('  40504040001  ')
            ->build();

        // Should preserve whitespace (no auto-trimming)
        $this->assertEquals('  40504040001  ', $identifier->identifier);
    }

    public function testMultipleTypeChanges(): void
    {
        $builder = new SemanticsIdentifierBuilder();

        // Set first type
        $builder->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER);
        $builder->withCountryCode('ee');
        $builder->withIdentifier('40504040001');

        // Change type before building
        $identifier = $builder
            ->withType(NaturalIdentityType::PASSPORT)
            ->build();

        $this->assertEquals(NaturalIdentityType::PASSPORT, $identifier->type);
    }

    public function testAllCountryCombinations(): void
    {
        $countryCodes = ['ee', 'lt', 'lv', 'ua', 'gb', 'us', 'ru', 'de', 'fr'];

        foreach ($countryCodes as $code) {
            $builder = new SemanticsIdentifierBuilder();
            $identifier = $builder
                ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
                ->withCountryCode($code)
                ->withIdentifier('40504040001')
                ->build();

            $this->assertEquals(strtoupper($code), $identifier->countryCode);
        }
    }

    public function testBuilderFluentInterfaceWithMultipleChains(): void
    {
        $identifier = (new SemanticsIdentifierBuilder())
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier('40504040001')
            ->build();

        $this->assertInstanceOf(SemanticsIdentifier::class, $identifier);
    }

    public function testIdentifierWithNullValue(): void
    {
        $this->expectException(\TypeError::class);

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode('ee')
            ->withIdentifier(null)
            ->build();
    }

    public function testCountryCodeWithNullValue(): void
    {
        $this->expectException(\TypeError::class);

        $builder = new SemanticsIdentifierBuilder();
        $builder
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withCountryCode(null)
            ->withIdentifier('40504040001')
            ->build();
    }
}
