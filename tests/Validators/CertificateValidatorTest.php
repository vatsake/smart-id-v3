<?php

namespace Vatsake\SmartIdV3\Tests\Validators;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateKeyUsageException;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificatePolicyException;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateQcException;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;
use Vatsake\SmartIdV3\Utils\PemFormatter;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use Vatsake\SmartIdV3\Validators\Session\CertificateValidator;

class CertificateValidatorTest extends TestCase
{
    use MockConfigTrait;

    private const NOTIFICATION_AUTH_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/auth/notification/session-response.json';

    private const INVALID_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/INVALID-CERTIFICATE.crt';
    private const VALID_AUTH_NQ_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-AUTH-NQ-20991231.crt';
    private const VALID_AUTH_LEGACY_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-AUTH-LEGACY-20991231.crt';
    private const VALID_SIGNING_Q_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/OK-SIGN-Q-PNOEE-40504040001.crt';
    private const VALID_SIGNING_NQ_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-SIGNING-NQ-20991231.crt';
    private const INVALID_SIGNING_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-SIGNING-BAD-20991231.crt';
    private const VALID_SIGNING_Q_VALIDQC_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-SIGNING-Q-VALIDQC-20991231.crt';
    private const VALID_SIGNING_Q_RANDOMQC_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-SIGNING-Q-RANDOMQC-20991231.crt';

    private function getSession(string $path)
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $rpChallenge = RpChallenge::generate();
        $req = NotificationAuthRequest::builder()
            ->withInteractions('Hello world')
            ->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();
        $session = $smartId->notification()->authentication()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');

        $response = json_decode(file_get_contents($path), true);
        $smartId = new SmartId($this->createMockConfig($response));
        return $smartId->session($session)->getAuthSession();
    }

    public function testValidateSmartIdPolicyOidsWithValidCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateSmartIDPolicyOids');

        $pem = PemFormatter::addPemHeaders($session->certificate->valueInBase64);
        $expectedLevel = $session->certificate->certificateLevel;

        $method->invoke($validator, $pem, $expectedLevel);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateSmartIdPolicyOidsThrowsExceptionForInvalidCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateSmartIDPolicyOids');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::INVALID_CERTIFICATE_PATH));
        $expectedLevel = $session->certificate->certificateLevel;

        $this->expectException(CertificatePolicyException::class);
        $method->invoke($validator, $pem, $expectedLevel);
    }

    public function testValidateSmartIdPolicyOidsThrowsExceptionForMismatchedCertificateLevel()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateSmartIDPolicyOids');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::VALID_AUTH_NQ_CERTIFICATE_PATH));

        $this->expectException(CertificatePolicyException::class);
        $method->invoke($validator, $pem, CertificateLevel::QUALIFIED);
    }

    public function testValidateSigningKeyPoliciesWithValidSigningCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateSigningKeyPolicies');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::VALID_SIGNING_Q_VALIDQC_CERTIFICATE_PATH));

        $method->invoke($validator, $pem);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateSigningKeyPoliciesThrowsExceptionWhenNonRepudiationKeyUsageMissing()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateSigningKeyPolicies');

        $pem = PemFormatter::addPemHeaders($session->certificate->valueInBase64);

        $this->expectException(CertificateKeyUsageException::class);
        $method->invoke($validator, $pem);
    }

    public function testValidateAuthKeyPoliciesWithValidCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateAuthKeyPolicies');

        $pem = PemFormatter::addPemHeaders($session->certificate->valueInBase64);

        $method->invoke($validator, $pem);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateAuthKeyPoliciesWithLegacyValidCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateAuthKeyPolicies');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::VALID_AUTH_LEGACY_CERTIFICATE_PATH));

        $method->invoke($validator, $pem);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateAuthKeyPoliciesThrowsExceptionForInvalidCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateAuthKeyPolicies');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::INVALID_CERTIFICATE_PATH));

        $this->expectException(CertificateKeyUsageException::class);
        $method->invoke($validator, $pem);
    }

    public function testValidateQcStatementsWithQualifiedSigningCertificate()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateQcStatements');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::VALID_SIGNING_Q_VALIDQC_CERTIFICATE_PATH));

        $method->invoke($validator, $pem, CertificateLevel::QUALIFIED);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateQcStatementsPassesForAdvancedLevel()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateQcStatements');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::INVALID_CERTIFICATE_PATH));

        $method->invoke($validator, $pem, CertificateLevel::ADVANCED);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateQcStatementsThrowsExceptionForInvalidCertificateWithQualifiedLevel()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateQcStatements');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::INVALID_CERTIFICATE_PATH));

        $this->expectException(CertificateQcException::class);
        $method->invoke($validator, $pem, CertificateLevel::QUALIFIED);
    }

    public function testValidateQcStatementsThrowsExceptionWhenQualifiedExpectedButAdvancedCertificateProvided()
    {
        $session = $this->getSession(self::NOTIFICATION_AUTH_RESPONSE_FILE_PATH);
        $validator = new CertificateValidator($session, $this->createMockConfig());

        $method = new ReflectionMethod(CertificateValidator::class, 'validateQcStatements');

        $pem = PemFormatter::addPemHeaders(file_get_contents(self::VALID_SIGNING_NQ_CERTIFICATE_PATH));

        $this->expectException(CertificateQcException::class);
        $method->invoke($validator, $pem, CertificateLevel::QUALIFIED);
    }
}
