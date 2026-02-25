<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Identity;

use Vatsake\SmartIdV3\Builders\Identity\SemanticsIdentifierBuilder;
use Vatsake\SmartIdV3\Enums\NaturalIdentityType;

class SemanticsIdentifier
{
    public function __construct(
        public readonly NaturalIdentityType $type,
        public readonly string $countryCode,
        public readonly string $identifier,
    ) {
    }

    public static function builder(): SemanticsIdentifierBuilder
    {
        return new SemanticsIdentifierBuilder();
    }

    public static function fromString(string $formattedString): SemanticsIdentifier
    {
        $type = NaturalIdentityType::from(strtoupper(substr($formattedString, 0, 3)));
        $countryCode = substr($formattedString, 3, 2);
        $identifier = substr($formattedString, 6);

        return new SemanticsIdentifier($type, $countryCode, $identifier);
    }

    /**
     * Convenience method for creating a SemanticsIdentifier from a personal number, which is the most common use case
     */
    public static function fromPersonalNumber(string $countryCode, string $number): self
    {
        return self::builder()
            ->withCountryCode($countryCode)
            ->withType(NaturalIdentityType::NATIONAL_PERSONAL_NUMBER)
            ->withIdentifier($number)
            ->build();
    }

    public function formattedString(): string
    {
        return sprintf('%s%s-%s', $this->type->value, $this->countryCode, $this->identifier);
    }
}
