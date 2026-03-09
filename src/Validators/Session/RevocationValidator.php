<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators\Session;

use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Validators\RevocationValidator as RootRevocationValidator;

class RevocationValidator implements SessionValidatorInterface
{
    private ?LoggerInterface $logger;

    public function __construct(
        private SigningSession|AuthSession $session,
        private SmartIdConfig $config
    ) {
        $this->logger = $config->getLogger();
    }

    public function validate(): void
    {
        $validator = new RootRevocationValidator($this->config);
        $pem = $this->session->certificate->valueInBase64;
        $validator->validateCertificateRevocation($pem);
        $this->logger?->debug('SMART-ID certificate revocation validation successful.');
    }
}
