<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Session;

use Vatsake\SmartIdV3\Builders\Session\SessionValidatorBuilder;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\InteractionType;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\DocumentUnusableException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ExpectedLinkedSessionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\IncompleteSessionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ProtocolFailureException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\RequiredInteractionNotSupportedByAppException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ServerErrorException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\SessionTimeoutException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\UserRefusedCertChoiceException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\UserRefusedException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\UserRefusedInteractionException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\WrongVcException;
use Vatsake\SmartIdV3\Features\SessionContract;
use Vatsake\SmartIdV3\Responses\Certificate;

abstract class BaseSession
{
    public readonly SessionState $state;
    public readonly SessionEndResult|null $endResult;
    public readonly string|null $identifier;
    public readonly ?Certificate $certificate;
    public readonly ?SignatureProtocol $signatureProtocol;
    public readonly ?InteractionType $interactionTypeUsed;

    public function __construct(
        string $state,
        protected SessionContract $session,
        protected SmartIdConfig $config,
        ?array $result,
        ?string $signatureProtocol,
        ?array $signature,
        ?array $cert,
        ?string $interactionTypeUsed,
        public readonly ?string $deviceIp,
        public readonly ?array $ignoredProperties,
    ) {
        $this->state = SessionState::from($state);
        if ($result !== null) {
            $this->endResult = SessionEndResult::from($result['endResult']);
            $this->identifier = $result['documentNumber'] ?? null;

            if ($this->endResult === SessionEndResult::OK) {
                $this->certificate = new Certificate(
                    $cert['value'],
                    $cert['certificateLevel']
                );
                $this->signatureProtocol = SignatureProtocol::tryFrom($signatureProtocol ?? ''); // CertChoice doesn't have this
                $this->interactionTypeUsed = InteractionType::tryFrom($interactionTypeUsed ?? ''); // CertChoice doesn't have this
                $this->initializeSignature($signature);
            }
        }
    }

    /**
     * Initialize signature based on session type. Implemented by subclasses.
     */
    abstract protected function initializeSignature(?array $signature): void;

    protected function isComplete(): bool
    {
        return $this->state === SessionState::COMPLETE;
    }

    /**
     * Checks if the session is complete and ended with OK result, meaning other fields can be safely accessed.
     */
    public function isSuccessful(): bool
    {
        return $this->isComplete() && $this->endResult === SessionEndResult::OK;
    }

    public function getSignedData(): string
    {
        return $this->session->getSignedData();
    }

    public function getInteractions(): string
    {
        return $this->session->getInteractions();
    }

    public function getInitialCallbackUrl(): string
    {
        return $this->session->getInitialCallbackUrl();
    }

    abstract public function validate();

    protected function validateEndResult(): void
    {
        switch ($this->endResult) {
            case SessionEndResult::USER_REFUSED:
                throw new UserRefusedException();
            case SessionEndResult::TIMEOUT:
                throw new SessionTimeoutException();
            case SessionEndResult::DOCUMENT_UNUSABLE:
                throw new DocumentUnusableException();
            case SessionEndResult::WRONG_VC:
                throw new WrongVcException();
            case SessionEndResult::REQUIRED_INTERACTION_NOT_SUPPORTED_BY_APP:
                throw new RequiredInteractionNotSupportedByAppException();
            case SessionEndResult::USER_REFUSED_CERT_CHOICE:
                throw new UserRefusedCertChoiceException();
            case SessionEndResult::USER_REFUSED_INTERACTION:
                throw new UserRefusedInteractionException();
            case SessionEndResult::PROTOCOL_FAILURE:
                throw new ProtocolFailureException();
            case SessionEndResult::EXPECTED_LINKED_SESSION:
                throw new ExpectedLinkedSessionException();
            case SessionEndResult::SERVER_ERROR:
                throw new ServerErrorException();
        }
    }
}
