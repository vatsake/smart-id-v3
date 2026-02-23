<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Requests\Concerns;

trait ToArray
{
    public function toArray(): array
    {
        $arr = [];
        $excluded = property_exists($this, 'excludedFields') ? $this->excludedFields : [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($key !== 'excludedFields' && $value !== null && !in_array($key, $excluded)) {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }
}
