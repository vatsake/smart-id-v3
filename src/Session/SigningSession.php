<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Session;

use Vatsake\SmartIdV3\Builders\Session\SessionValidatorBuilder;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\IncompleteSessionException;
use Vatsake\SmartIdV3\Factories\SignatureFactory;
use Vatsake\SmartIdV3\Responses\RawDigestSignature;

class SigningSession extends BaseSession
{
    public readonly ?RawDigestSignature $signature;

    protected function initializeSignature(?array $signature): void
    {
        $this->signature = SignatureFactory::createRawDigest($signature);
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
     * @see https://phpseclib.com/docs/rsa#rsasignature_pkcs1
     */
    public function validate(): SessionValidatorBuilder
    {
        if (!$this->isComplete()) {
            throw new IncompleteSessionException();
        }
        $this->validateEndResult();
        return new SessionValidatorBuilder($this, $this->config);
    }
}
