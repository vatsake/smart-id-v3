<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Identity;

use Vatsake\SmartIdV3\Enums\NaturalIdentityType;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;

class SemanticsIdentifierBuilder
{
    private NaturalIdentityType $type;
    private string $countryCode;
    private string $identifier;

    public function withType(NaturalIdentityType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function withCountryCode(string $countryCode): self
    {
        $this->countryCode = strtoupper($countryCode);
        return $this;
    }

    public function withIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    private function validateCountryCode(): void
    {
        if (strlen($this->countryCode) !== 2) {
            throw new \InvalidArgumentException('Country code must be exactly 2 characters');
        }
    }

    private function validateMandatoryParams()
    {
        $mandatoryParams = ['type', 'countryCode', 'identifier'];
        foreach ($mandatoryParams as $param) {
            if (!isset($this->$param) || empty($this->$param)) {
                throw new \InvalidArgumentException("Missing mandatory parameter: $param");
            }
        }
    }

    private function validate(): void
    {
        $this->validateMandatoryParams();
        $this->validateCountryCode();
    }

    public function build(): SemanticsIdentifier
    {
        $this->validate();
        return new SemanticsIdentifier($this->type, $this->countryCode, $this->identifier);
    }
}
