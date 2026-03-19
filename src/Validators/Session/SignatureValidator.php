<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Validators\Session;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Utils\PemFormatter;

class SignatureValidator implements SessionValidatorInterface
{
    private ?LoggerInterface $logger;

    public function __construct(
        private SigningSession|AuthSession $session,
        private SmartIdConfig $config
    ) {
        $this->logger = $config->getLogger();
    }

    /**
     * @throws SignatureException if signature validation fails
     */
    public function validate(): void
    {
        $signatureValue = base64_decode($this->session->signature->value, true);
        if ($signatureValue === false) {
            $this->logger?->error('Invalid base64 in signature value', ['signature' => $this->session->signature->value]);
            throw new SignatureException('Invalid base64 in signature value');
        }
        if ($this->session instanceof SigningSession) {
            $this->validateSigningSessionSignature($signatureValue);
        } elseif ($this->session instanceof AuthSession) {
            $this->validateAuthSessionSignature($signatureValue);
        }
        $this->logger?->debug('Signature validation successful.');
    }

    private function validateSigningSessionSignature(string $signatureValue): void
    {
        $verified = $this->verifySignature($signatureValue, $this->session->getSignedData());

        if (!$verified) {
            $this->logger?->error('Signature validation failed', ['signature' => $this->session->signature->value]);
            throw new SignatureException();
        }
    }

    private function validateAuthSessionSignature(string $signatureValue): void
    {
        $payload = $this->getAcspV2Payload();
        $verified = $this->verifySignature($signatureValue, $payload);

        if (!$verified) {
            $this->logger?->error('Signature validation failed', ['signature' => $this->session->signature->value, 'payload' => $payload]);
            throw new SignatureException();
        }
    }

    private function verifySignature(string $signatureValue, string $payload): bool
    {
        if ($this->session->signature->signatureAlgorithm === SignatureAlgorithm::RSASSA_PSS) {
            return $this->validateSignatureWithPSS($signatureValue, $payload);
        } else {
            return $this->validateSignatureWithPKCS1($signatureValue, $payload);
        }
    }

    private function validateSignatureWithPKCS1(string $signatureValue, string $payload): bool
    {
        $signerPublicKey = openssl_pkey_get_public($this->session->certificate->getX509Resource());
        $alg = substr($this->session->signature->signatureAlgorithm->value, 0, 6);
        return openssl_verify($payload, $signatureValue, $signerPublicKey, $alg) === 1;
    }

    private function validateSignatureWithPSS(string $signatureValue, string $payload): bool
    {
        /** @var \phpseclib3\Crypt\RSA\PublicKey $pub */
        $pub = PublicKeyLoader::load(PemFormatter::addPemHeaders($this->session->certificate->valueInBase64));
        $pub = $pub->withPadding(RSA::SIGNATURE_PSS)
            ->withHash($this->session->signature->signatureHashAlgorithm->getName())
            ->withMGFHash($this->session->signature->signatureMaskGenHashAlgorithm->getName())
            ->withSaltLength($this->session->signature->saltLength);

        return $pub->verify($payload, $signatureValue);
    }

    private function getAcspV2Payload(): string
    {
        $interactionsHash = base64_encode(hash('sha256', $this->session->getInteractions(), true));

        return implode('|', [
            $this->config->getScheme(),
            $this->session->signatureProtocol->value,
            $this->session->signature->serverRandom,
            $this->session->getSignedData(),
            $this->session->signature->userChallenge,
            base64_encode($this->config->getRelyingPartyName()),
            '',
            $interactionsHash,
            $this->session->interactionTypeUsed->value,
            $this->session->getInitialCallbackUrl(),
            $this->session->signature->flowType->value
        ]);
    }
}
