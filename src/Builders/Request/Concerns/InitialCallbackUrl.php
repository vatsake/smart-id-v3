<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request\Concerns;

use InvalidArgumentException;

trait InitialCallbackUrl
{
    protected ?string $initialCallbackUrl = null;

    /**
     * Only use with device link flows
     */
    public function withInitialCallbackUrl(string $url): self
    {
        $this->initialCallbackUrl = $url;
        return $this;
    }

    protected function validateInitialCallbackUrl(): void
    {
        if (
            !empty($this->initialCallbackUrl) &&
            preg_match('/^https:\/\/([^|#]+)$/', $this->initialCallbackUrl) !== 1
        ) {
            throw new InvalidArgumentException(
                'Initial Callback URL is invalid. It must use HTTPS and cannot contain URL fragments.'
            );
        }
    }
}
