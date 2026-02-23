<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request\Concerns;

use InvalidArgumentException;

trait Nonce
{
    protected ?string $nonce = null;

    public function withNonce(string $nonce): self
    {
        $this->nonce = $nonce;
        return $this;
    }

    protected function validateNonce(): void
    {
        if (isset($this->nonce) && strlen($this->nonce) > 30) {
            throw new InvalidArgumentException('Nonce cannot be longer than 30 characters.');
        }
    }
}
