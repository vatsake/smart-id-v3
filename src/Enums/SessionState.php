<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum SessionState: string
{
    case RUNNING = 'RUNNING';
    case COMPLETE = 'COMPLETE';
}
