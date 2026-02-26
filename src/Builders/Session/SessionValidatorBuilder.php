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
use Vatsake\SmartIdV3\Exceptions\Validation\InitialCallbackUrlParamMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\SessionSecretMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Exceptions\Validation\UserChallengeMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;
use Vatsake\SmartIdV3\Utils\PemFormatter;
use Vatsake\SmartIdV3\Utils\UrlSafe;

/**
 * Builder for validating Smart ID session responses.
 *
 * Provides configurable validation of authentication and signing sessions with support for:
 * - Signature verification (PKCS#1 v1.5 and PSS padding schemes)
 * - Certificate chain validation and Smart-ID policy compliance
 * - Certificate revocation status via OCSP
 * - Callback URL parameter validation for Web2App and App2App flows
 *
 * @see https://sk-eid.github.io/smart-id-documentation/rp-api/response_verification.html
 */
class SessionValidatorBuilder
{
    private bool $validateSignature = true;
    private bool $validateCertificate = false;
    private bool $validateRevocation = false;
    private bool $validateCallbackUrl = false;

    private string $expectedQueryParamValue;
    private string $queryParamValue;
    private string $sessionSecretDigest;
    private string $userChallengeVerifier;

    private ?LoggerInterface $logger = null;

    public function __construct(
        private SigningSession|AuthSession $session,
        private SmartIdConfig $config
    ) {
        $this->logger = $config->getLogger();
    }

    /**
     * Verifies that the signature in the session response was properly signed
     * by the user's certificate. Supports both PKCS#1 v1.5 and RSA-PSS padding
     * schemes with **SHA-256**, **SHA-384**, and **SHA-512** hash algorithms.
     *
     * For signing sessions: verifies signature against the data to be signed.
     * For authentication sessions: verifies signature against an ACSP v2 payload.
     *
     * @param bool $enabled Enable signature validation (default: true)
     * @return self
     * @see https://phpseclib.com/docs/rsa#rsasignature_pkcs1 for limitations
     */
    public function withSignatureValidation(bool $enabled = true): self
    {
        $this->validateSignature = $enabled;
        return $this;
    }

    /**
     * Certificate validation validates:
     * - Certificate chain can be traced to a trusted CA
     * - Certificate contains required Smart-ID policy OIDs
     * - Certificate has correct key usage and extended key usage
     * - Certificate contains required QC statements (for qualified signatures)
     * - Certificate validity period matches current time
     *
     * Note: Does not check revocation status. Use withRevocationValidation() for that.
     *
     * @param bool $enabled Enable certificate validation (default: false)
     * @return self
     */
    public function withCertificateValidation(bool $enabled = true): self
    {
        $this->validateCertificate = $enabled;
        return $this;
    }

    /**
     * Certificate revocation validation queries OCSP responders to verify the certificate has
     * not been revoked. OCSP responses include time validation, so system time/date must be
     * accurate. Includes a reasonable clock skew tolerance (5 minutes).
     *
     * @param bool $enabled Enable revocation validation (default: false)
     * @return self
     */
    public function withRevocationValidation(bool $enabled = true): self
    {
        $this->validateRevocation = $enabled;
        return $this;
    }

    /**
     * Validate callback URL parameters directly using raw values.
     *
     * Use this method when you have already extracted and decoded the callback URL
     * query parameters. All parameters must be provided when validation is enabled.
     *
     * @param bool $enabled Enable callback URL validation (default: true)
     * @param null|string $sessionSecretDigest Session secret
     * @param null|string $userChallengeVerifier User challenge (for auth)
     * @param null|string $expectedQueryParamValue Expected value of the unique parameter (e.g., from session)
     * @param null|string $queryParamValue Actual value from callback URL query string
     * @return self
     */
    public function withCallbackUrlValidationParameters(
        bool $enabled = true,
        ?string $sessionSecretDigest = null,
        ?string $userChallengeVerifier = null,
        ?string $expectedQueryParamValue = null,
        ?string $queryParamValue = null,
    ): self {
        $this->validateCallbackUrl = $enabled;

        if ($enabled) {
            if (empty($sessionSecretDigest) || empty($userChallengeVerifier)) {
                throw new ValidationException('Session secret digest and user challenge verifier must be provided for callback URL validation.');
            }
            if ($queryParamValue === null || $expectedQueryParamValue === null) {
                throw new ValidationException('Query parameter value and expected value must be provided for callback URL validation.');
            }
            $this->sessionSecretDigest = $sessionSecretDigest;
            $this->userChallengeVerifier = $userChallengeVerifier;
            $this->queryParamValue = $queryParamValue;
            $this->expectedQueryParamValue = $expectedQueryParamValue;
        }

        return $this;
    }

    /**
     * Validate callback URL parameters by providing the full URL.
     *
     * Convenience method that parses the callback URL to extract and validate parameters.
     * Automatically extracts sessionSecretDigest, userChallengeVerifier, and your custom
     * unique parameter from the query string.
     *
     * Use this instead of withCallbackUrlValidationParameters() when you can pass the
     * full callback URL directly.
     *
     * @param bool $enabled Enable callback URL validation
     * @param null|string $url Full callback URL including query parameters
     * @param null|string $expectedParamValue Expected value of the unique parameter (e.g., from session)
     * @param null|string $queryParamName Name of the unique query parameter to extract and validate (e.g., 'uid', 'state')
     * @return self
     */
    public function withCallbackUrlValidation(
        bool $enabled = true,
        ?string $url = null,
        ?string $expectedParamValue = null,
        ?string $queryParamName = null,
    ): self {
        if ($enabled) {
            if (empty($url) || empty($queryParamName)) {
                throw new ValidationException('URL and query parameter name must be provided for callback URL validation.');
            }
            $params = parse_url($url, PHP_URL_QUERY);
            parse_str($params, $queryParams);

            if (!isset($queryParams[$queryParamName])) {
                throw new ValidationException("Query parameter '{$queryParamName}' not found in callback URL.");
            }
            if (!isset($queryParams['sessionSecretDigest'])) {
                throw new ValidationException("Query parameter 'sessionSecretDigest' not found in callback URL.");
            }
            if (!isset($queryParams['userChallengeVerifier'])) {
                throw new ValidationException("Query parameter 'userChallengeVerifier' not found in callback URL.");
            }
            if ($expectedParamValue === null) {
                throw new ValidationException('Expected query parameter value must be provided for callback URL validation.');
            }
            $this->withCallbackUrlValidationParameters($enabled, $queryParams['sessionSecretDigest'], $queryParams['userChallengeVerifier'], $expectedParamValue, $queryParams[$queryParamName]);
        } else {
            $this->validateCallbackUrl = false;
        }
        return $this;
    }

    /**
     * Execute all enabled validations.
     *
     * All enabled validators must pass or an exception is thrown.
     * If any validator throws an exception, subsequent validators are not executed.
     *
     * @return void
     * @throws SignatureException if signature verification fails
     * @throws CertificateChainException if certificate chain validation fails
     * @throws CertificatePolicyException if required certificate policies are missing
     * @throws CertificateQcException if QC statements are invalid
     * @throws CertificateKeyUsageException if key usage or extended key usage is invalid
     * @throws SessionSecretMismatchException if session secret digest doesn't match
     * @throws InitialCallbackUrlParamMismatchException if callback URL parameter doesn't match
     * @throws UserChallengeMismatchException if user challenge doesn't match (auth only)
     * @throws OcspCertificateRevocationException if certificate is revoked
     * @throws OcspResponseTimeException if OCSP response time is outside acceptable window
     * @throws OcspSignatureException if OCSP response signature validation fails
     * @throws OcspKeyUsageException if OCSP responder certificate key usage is invalid
     * @throws ValidationException for generic validation failures
     */
    public function check(): void
    {
        if ($this->validateSignature) {
            $this->validateSignature();
        }

        if ($this->validateCertificate) {
            $this->validateSmartCertificate();
        }

        if ($this->validateCallbackUrl) {
            $this->validateCallbackUrl();
        }

        if ($this->validateRevocation) {
            $this->validateRevocation();
        }
    }

    /**
     * @throws SignatureException if signature is invalid or cannot be decoded
     */
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
        $this->logger?->debug('Signature validation successful.');
    }

    /**
     * @throws SignatureException if signature verification fails
     */
    private function validateSigningSessionSignature(string $signatureValue): void
    {
        $verified = $this->verifySignature($signatureValue, $this->session->getSignedData());

        if (!$verified) {
            $this->logger?->error('Signature validation failed', ['signature' => $this->session->signature->value]);
            throw new SignatureException();
        }
    }

    /**
     * @throws SignatureException if signature verification fails
     */
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

    /**
     * Build the ACSP v2 payload that was signed during authentication.
     *
     * @return string The ACSP v2 payload
     * @see https://sk-eid.github.io/smart-id-documentation/rp-api/signature_protocols.html#acsp_v2_digest_calculation
     */
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

    /**
     * @throws CertificateChainException if certificate chain is invalid
     * @throws CertificatePolicyException if required policies are missing
     * @throws CertificateQcException if QC statements are invalid
     * @throws CertificateKeyUsageException if key usage or extended key usage is invalid
     */
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
        $this->logger?->debug('SMART-ID certificate validation successful.');
    }

    /**
     * @throws CertificateChainException if certificate chain is invalid
     * @throws OcspCertificateRevocationException if certificate is revoked
     * @throws OcspResponseTimeException if OCSP response time is outside acceptable window
     * @throws OcspSignatureException if OCSP response signature is invalid
     * @throws OcspKeyUsageException if OCSP responder certificate key usage is invalid
     */
    private function validateRevocation(): void
    {
        $validator = new RevocationValidator($this->config);
        $pem = $this->session->certificate->valueInBase64;
        $validator->validateCertificateRevocation($pem);
        $this->logger?->debug('SMART-ID certificate revocation validation successful.');
    }

    /**
     * @throws SessionSecretMismatchException if session secret doesn't match
     * @throws InitialCallbackUrlParamMismatchException if parameter value doesn't match
     * @throws UserChallengeMismatchException if user challenge doesn't match (auth only)
     * @see https://sk-eid.github.io/smart-id-documentation/rp-api/callback_urls.html
     */
    private function validateCallbackUrl()
    {
        $this->validateSessionSecret();
        $this->validateCallbackQueryParam();
        if ($this->session instanceof AuthSession) {
            $this->validateUserChallenge();
        }
    }

    /**
     * @throws SessionSecretMismatchException if digest doesn't match
     */
    private function validateSessionSecret()
    {
        $secret = base64_decode($this->session->getSessionSecret());
        $hash = hash('sha256', $secret, true);
        $urlSafeHash = UrlSafe::toUrlSafe(base64_encode($hash));

        if ($urlSafeHash !== $this->sessionSecretDigest) {
            $this->logger?->debug('Session secret digest validation failed', ['expected' => $this->sessionSecretDigest, 'actual' => $urlSafeHash]);
            throw new SessionSecretMismatchException();
        }

        $this->logger?->debug('Session secret digest validation successful.');
    }

    /**
     * @throws InitialCallbackUrlParamMismatchException if parameter value doesn't match
     */
    private function validateCallbackQueryParam()
    {
        if ($this->expectedQueryParamValue !== $this->queryParamValue) {
            $this->logger?->debug('Callback URL query parameter validation failed', ['expected' => $this->expectedQueryParamValue, 'actual' => $this->queryParamValue]);
            throw new InitialCallbackUrlParamMismatchException();
        }
        $this->logger?->debug('Callback URL query parameter validation successful.');
    }

    /**
     * @throws UserChallengeMismatchException if challenge hash doesn't match
     */
    private function validateUserChallenge()
    {
        $userChallenge = $this->session->signature->userChallenge;
        $challengeHash = hash('sha256', $this->userChallengeVerifier, true);
        $urlSafeChallengeHash = UrlSafe::toUrlSafe(base64_encode($challengeHash));

        if ($userChallenge !== $urlSafeChallengeHash) {
            $this->logger?->debug('User challenge verifier validation failed', ['expected' => $userChallenge, 'actual' => $urlSafeChallengeHash]);
            throw new UserChallengeMismatchException();
        }
        $this->logger?->debug('User challenge verifier validation successful.');
    }
}
