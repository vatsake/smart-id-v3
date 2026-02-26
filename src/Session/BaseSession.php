<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Session;

use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Data\SessionData;
use Vatsake\SmartIdV3\Enums\InteractionType;
use Vatsake\SmartIdV3\Enums\SessionEndResult;
use Vatsake\SmartIdV3\Enums\SessionState;
use Vatsake\SmartIdV3\Enums\SignatureProtocol;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\DocumentUnusableException;
use Vatsake\SmartIdV3\Exceptions\SmartIdSession\ExpectedLinkedSessionException;
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
    public readonly string|null $documentNumber;
    public readonly ?Certificate $certificate;
    public readonly ?array $ignoredProperties;
    public readonly ?string $deviceIpAddress;

    /**
     * Only populated for authentication and signing sessions
     */
    public readonly ?SignatureProtocol $signatureProtocol;

    /**
     * Only populated for authentication and signing sessions
     */
    public readonly ?InteractionType $interactionTypeUsed;

    public function __construct(
        SessionData $sessionData,
        protected SessionContract $session,
        protected SmartIdConfig $config,
    ) {
        $this->state = SessionState::from($sessionData->state);
        $this->deviceIpAddress = $sessionData->deviceIp;
        $this->ignoredProperties = $sessionData->ignoredProperties;

        if ($sessionData->result === null) {
            $this->endResult = null;
            $this->documentNumber = null;
            $this->certificate = null;
            $this->signatureProtocol = null;
            $this->interactionTypeUsed = null;
            return;
        }

        $this->endResult = SessionEndResult::from($sessionData->result['endResult']);
        $this->documentNumber = $sessionData->result['documentNumber'] ?? null;

        if ($this->endResult === SessionEndResult::OK) {
            $this->certificate = new Certificate(
                $sessionData->cert['value'],
                $sessionData->cert['certificateLevel']
            );
            $this->signatureProtocol = SignatureProtocol::tryFrom($sessionData->signatureProtocol ?? '');
            $this->interactionTypeUsed = InteractionType::tryFrom($sessionData->interactionTypeUsed ?? '');
            $this->initializeSignature($sessionData->signature);
        }
    }

    /**
     * Initialize signature based on session type.
     */
    abstract protected function initializeSignature(?array $signature): void;

    /**
     * Checks if the session is complete
     */
    public function isComplete(): bool
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

    /**
     * Only populated for signature and authentication sessions
     */
    public function getSignedData(): string
    {
        return $this->session->getSignedData();
    }

    /**
     * Only populated for signature and authentication sessions
     */
    public function getInteractions(): string
    {
        return $this->session->getInteractions();
    }

    /**
     * Only populated for signature and authentication sessions
     */
    public function getInitialCallbackUrl(): string
    {
        return $this->session->getInitialCallbackUrl();
    }

    /**
     * Only populated for device link flows
     */
    public function getSessionSecret(): string
    {
        return $this->session->getSessionSecret();
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
