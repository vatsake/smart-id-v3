<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Session;

use Vatsake\SmartIdV3\Exceptions\SmartIdSession\IncompleteSessionException;
use Vatsake\SmartIdV3\Factories\SignatureFactory;
use Vatsake\SmartIdV3\Responses\Signature\CertificateChoiceSignature;

class CertificateChoiceSession extends BaseSession
{
    public readonly ?CertificateChoiceSignature $signature;

    protected function initializeSignature(?array $signature): void
    {
        $this->signature = SignatureFactory::createCertificateChoice($signature);
    }

    /**
     * Validation builder
     * 
     * @throws IncompleteSessionException
     * @throws UserRefusedException
     * @throws SessionTimeoutException
     * @throws DocumentUnusableException
     * @throws WrongVcException
     * @throws RequiredInteractionNotSupportedByAppException
     * @throws UserRefusedCertChoiceException
     * @throws UserRefusedInteractionException
     * @throws ProtocolFailureException
     * @throws ExpectedLinkedSessionException
     * @throws ServerErrorException
     */
    public function validate()
    {
        if (!$this->isComplete()) {
            throw new IncompleteSessionException();
        }
        $this->validateEndResult();
    }
}
