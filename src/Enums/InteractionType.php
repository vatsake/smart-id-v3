<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Enums;

enum InteractionType: string
{
    case DISPLAY_TEXT_AND_PIN = 'displayTextAndPIN';
    case CONFIRMATION_MESSAGE = 'confirmationMessage';
    case CONFIRMATION_MESSAGE_AND_VERIFICATION_CODE_CHOICE = 'confirmationMessageAndVerificationCodeChoice';
}
