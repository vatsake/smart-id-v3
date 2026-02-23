<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Session;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Validators\SmartIdCertificateValidator;
use Vatsake\SmartIdV3\Validators\RevocationValidator;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SignatureAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;
use Vatsake\SmartIdV3\Utils\PemFormatter;

class SessionValidatorBuilder
{
    private bool $validateSignature = true;
    private bool $validateCertificate = false;
    private bool $validateRevocation = false;

    private ?LoggerInterface $logger = null;

    public function __construct(
        private SigningSession|AuthSession $session,
        private SmartIdConfig $config
    ) {
        $this->logger = $config->getLogger();
    }

    /**
     * Checks signature validity.
     * 
     * Phpseclib supports validation of the following hash algorithms for RSA signatures:
     * - SHA-256
     * - SHA-384
     * - SHA-512
     * @see https://phpseclib.com/docs/rsa#rsasignature_pkcs1
     */
    public function withSignatureValidation(bool $enabled = true): self
    {
        $this->validateSignature = $enabled;
        return $this;
    }

    /**
     * Checks certificate validity, chain, and compliance with Smart-ID policies.
     */
    public function withCertificateValidation(bool $enabled = true): self
    {
        $this->validateCertificate = $enabled;
        return $this;
    }

    /**
     * Checks certificate revocation status using OCSP.
     */
    public function withRevocationValidation(bool $enabled = true): self
    {
        $this->validateRevocation = $enabled;
        return $this;
    }

    /**
     * @throws ValidationException if validations fail
     */
    public function check(): void
    {
        if ($this->validateSignature) {
            $this->validateSignature();
        }

        if ($this->validateCertificate) {
            $this->validateSmartCertificate();
        }

        if ($this->validateRevocation) {
            $this->validateRevocation();
        }
    }

    private function validateSignature(): void
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

    /**
     * Verifies a signature using the appropriate algorithm (PSS or PKCS1)
     */
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
            ->withSaltLength($this->session->signature->signatureSaltLength);

        return $pub->verify($payload, $signatureValue);
    }

    private function getAcspV2Payload(): string
    {
        $interactionsHash = base64_encode(hash('sha256', $this->session->getInteractions(), true));

        return implode('|', [
            'smart-id',
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

    private function validateSmartCertificate(): void
    {
        $validator =  new SmartIdCertificateValidator($this->config);

        $pem = $this->session->certificate->valueInBase64;
        $expectedLevel = $this->session->certificate->certificateLevel;

        if ($this->session instanceof SigningSession) {
            $validator->validateSmartIdSigningCertificate($pem, $expectedLevel);
        } elseif ($this->session instanceof AuthSession) {
            $validator->validateAuthCertificate($pem, $expectedLevel);
        }
    }

    private function validateRevocation(): void
    {
        $validator = new RevocationValidator($this->config);
        $pem = $this->session->certificate->valueInBase64;
        $validator->validateCertificateRevocation($pem);
    }
}
