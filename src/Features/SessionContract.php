<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Features;

interface SessionContract
{
    public function getSessionId(): string;
    public function getSignedData(): string;
    public function getInteractions(): string;
    public function getInitialCallbackUrl(): string;
    public function getSessionSecret(): string;
}
