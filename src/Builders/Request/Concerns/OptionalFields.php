<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request\Concerns;

trait OptionalFields
{
    protected function addOptionalFields(array $data, array $optionalFields): array
    {
        foreach ($optionalFields as $propertyName) {
            if (isset($this->$propertyName)) {
                $value = $this->$propertyName;
                // Enum
                $data[$propertyName] = method_exists($value, 'tryFrom') ? $value->value : $value;
            }
        }
        return $data;
    }
}
