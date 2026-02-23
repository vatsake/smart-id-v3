<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request\Concerns;

use InvalidArgumentException;

trait Interactions
{
    public const DISPLAY_TEXT_AND_PIN = 'displayTextAndPIN';
    public const CONFIRMATION_MESSAGE = 'confirmationMessage';

    protected array $interactions = [];

    public function withInteractions(?string $displayText60 = null, ?string $displayText200 = null): self
    {
        $interactions = [];

        if ($displayText60 !== null) {
            $interactions[] = [
                'displayText60' => $displayText60,
                'type' => self::DISPLAY_TEXT_AND_PIN
            ];
        }

        if ($displayText200 !== null) {
            $interactions[] = [
                'displayText200' => $displayText200,
                'type' => self::CONFIRMATION_MESSAGE
            ];
        }

        $this->interactions = $interactions;
        return $this;
    }

    protected function findInteractionByType(string $type): ?array
    {
        foreach ($this->interactions as $interaction) {
            if ($interaction['type'] === $type) {
                return $interaction;
            }
        }
        return null;
    }

    protected function validateInteractions(): void
    {
        if (empty($this->interactions)) {
            throw new InvalidArgumentException('At least one interaction must be provided.');
        }

        $displayText60 = $this->findInteractionByType(self::DISPLAY_TEXT_AND_PIN);
        if ($displayText60 !== null && strlen($displayText60['displayText60']) > 60) {
            throw new InvalidArgumentException('displayText60 exceeds maximum length of 60 characters.');
        }

        $displayText200 = $this->findInteractionByType(self::CONFIRMATION_MESSAGE);
        if ($displayText200 !== null && strlen($displayText200['displayText200']) > 200) {
            throw new InvalidArgumentException('displayText200 exceeds maximum length of 200 characters.');
        }
    }
}
