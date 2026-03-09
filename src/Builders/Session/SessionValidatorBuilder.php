<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Session;

use Psr\Log\LoggerInterface;
use Vatsake\SmartIdV3\Session\SigningSession;
use Vatsake\SmartIdV3\Session\AuthSession;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Exceptions\Validation\InitialCallbackUrlParamMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\SessionSecretMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Exceptions\Validation\UserChallengeMismatchException;
use Vatsake\SmartIdV3\Exceptions\Validation\ValidationException;
use Vatsake\SmartIdV3\Validators\Session\CallbackUrlValidator;
use Vatsake\SmartIdV3\Validators\Session\CertificateValidator;
use Vatsake\SmartIdV3\Validators\Session\RevocationValidator as OcspRevocationValidator;
use Vatsake\SmartIdV3\Validators\Session\SignatureValidator as SessionSignatureValidator;

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
     * @throws ValidationException for generic validation failures (parent class of all validation exceptions)
     */
    public function check(): void
    {
        if ($this->validateSignature) {
            (new SessionSignatureValidator($this->session, $this->config))->validate();
        }

        if ($this->validateCertificate) {
            (new CertificateValidator($this->session, $this->config))->validate();
        }

        if ($this->validateCallbackUrl) {
            (new CallbackUrlValidator(
                $this->session,
                $this->logger,
                $this->sessionSecretDigest,
                $this->userChallengeVerifier,
                $this->expectedQueryParamValue,
                $this->queryParamValue
            ))->validate();
        }

        if ($this->validateRevocation) {
            (new OcspRevocationValidator($this->session, $this->config))->validate();
        }
    }
}
