<?php

namespace Vatsake\SmartIdV3\Exceptions\SmartIdSession;

class ExpectedLinkedSessionException extends SmartIdSessionException
{
    public function __construct()
    {
        parent::__construct('The app received a different transaction while waiting for the linked session.');
    }
}
