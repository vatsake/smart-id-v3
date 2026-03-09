<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators\Session;

use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;

interface SessionValidatorInterface
{
    /**
     * Executes the validation rules.
     *
     * @throws ValidationException if validation fails.
     */
    public function validate(): void;
}
