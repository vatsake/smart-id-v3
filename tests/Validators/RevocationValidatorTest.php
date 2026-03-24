<?php

namespace Vatsake\SmartIdV3\Tests\Validators;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\CrlUrlMissingException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspCertificateRevocationException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspKeyUsageException;
use Vatsake\SmartIdV3\Exceptions\Validation\OcspResponseTimeException;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Responses\Ocsp\OcspResponse;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;
use Vatsake\SmartIdV3\Utils\PemFormatter;
use Vatsake\SmartIdV3\Validators\Session\RevocationValidator;

class RevocationValidatorTest extends TestCase
{
    use MockConfigTrait;

    private const INVALID_CERT_FILE_PATH = __DIR__ . '/../resources/subject-certificates/INVALID-CERTIFICATE.crt';
    private const SUBJECT_CERTIFICATE_FILE_PATH = __DIR__ . '/../resources/subject-certificates/OK-SIGN-Q-PNOEE-40504040001.crt';
    private const NOTIFICATION_SIGNING_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/signing/notification/session-response.json';
    private const OCSP_RESPONSE_BIN_PATH = __DIR__ . '/../resources/responses/ocsp/40504040001-response.bin';
    private const CRL_RESPONSE_BIN_PATH = __DIR__ . '/../resources/responses/crl/crl-response.bin';

    private function getSigningSession()
    {
        $sessionId = '1234567890';
        $smartId = new SmartId($this->createMockConfig(['sessionID' => $sessionId]));

        $req = NotificationSigningRequest::builder()
            ->withInteractions('Hello world')
            ->withData('test 1234', HashAlgorithm::SHA_512)
            ->build();
        $session = $smartId->notification()->signing()->startDocument($req, 'PNOEE-40504040001-DEM0-Q');

        $response = json_decode(file_get_contents(self::NOTIFICATION_SIGNING_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));
        return $smartId->session($session)->getSigningSession();
    }

    private function getReflectionMethod(string $methodName): \ReflectionMethod
    {
        return new \ReflectionMethod(RevocationValidator::class, $methodName);
    }

    private function createRevocationValidator(): RevocationValidator
    {
        return new RevocationValidator(
            $this->createMockConfig(file_get_contents(self::OCSP_RESPONSE_BIN_PATH)),
            $this->getSigningSession()
        );
    }

    private function createPartialMockOcspResponse(array $methods = [], callable $callback): OcspResponse
    {
        $binary = file_get_contents(self::OCSP_RESPONSE_BIN_PATH);

        $mock = $this->getMockBuilder(OcspResponse::class)
            ->setConstructorArgs([$binary])
            ->onlyMethods(array_merge(['getThisUpdate', 'getNextUpdate'], $methods))
            ->getMock();

        $callback($mock);

        return $mock;
    }

    public function testRevocationValidatorResponseTimeValidationPasses(): void
    {
        $mock = $this->createPartialMockOcspResponse([], function (MockObject $mock) {
            $mock->method('getThisUpdate')->willReturn(time() - 60);
            $mock->method('getNextUpdate')->willReturn(time() + 3600);
        });

        $method = $this->getReflectionMethod('validateResponseTime');

        $validator = $this->createRevocationValidator();

        $method->invoke($validator, $mock);
        $this->expectNotToPerformAssertions();
    }

    public function testRevocationValidatorResponseTimeValidationFails(): void
    {
        $mock = $this->createPartialMockOcspResponse([], function (MockObject $mock) {
            $mock->method('getThisUpdate')->willReturn(time() - 3600);
            $mock->method('getNextUpdate')->willReturn(time() - 60);
        });

        $method = $this->getReflectionMethod('validateResponseTime');

        $validator = $this->createRevocationValidator();

        $this->expectException(OcspResponseTimeException::class);
        $method->invoke($validator, $mock);
    }

    public function testRevocationValidatorResponseTimeValidationDueToNextUpdateFails(): void
    {
        $mock = $this->createPartialMockOcspResponse([], function (MockObject $mock) {
            $mock->method('getThisUpdate')->willReturn(time() - 60);
            $mock->method('getNextUpdate')->willReturn(time() - 350);
        });

        $method = $this->getReflectionMethod('validateResponseTime');

        $validator = $this->createRevocationValidator();

        $this->expectException(OcspResponseTimeException::class);
        $method->invoke($validator, $mock);
    }

    public function testRevocationValidatorCertificateStatusPasses(): void
    {
        $mock = $this->createPartialMockOcspResponse([], function (MockObject $mock) {});

        $method = $this->getReflectionMethod('validateCertificateStatus');

        $validator = $this->createRevocationValidator();

        $method->invoke($validator, $mock);
        $this->expectNotToPerformAssertions();
    }

    public function testRevocationValidatorCertificateStatusFails(): void
    {
        $mock = $this->createPartialMockOcspResponse(['getCertificateStatus'], function (MockObject $mock) {
            $mock->method('getCertificateStatus')->willReturn('bad');
        });

        $method = $this->getReflectionMethod('validateCertificateStatus');

        $validator = $this->createRevocationValidator();

        $this->expectException(OcspCertificateRevocationException::class);
        $method->invoke($validator, $mock);
    }

    public function testRevocationValidatorResponderCertificatePasses(): void
    {
        $mock = $this->createPartialMockOcspResponse([], function (MockObject $mock) {});

        $method = $this->getReflectionMethod('validateResponderCertificate');

        $validator = $this->createRevocationValidator();

        $method->invoke($validator, $mock);
    }

    public function testRevocationValidatorResponderKeyUsageFails(): void
    {
        $mock = $this->createPartialMockOcspResponse([], function (MockObject $mock) {});

        $responderCertDer = $mock->getResponderCertificate();
        $responderCertPem = PemFormatter::addPemHeaders(base64_encode($responderCertDer));
        $parsedCert = openssl_x509_parse($responderCertPem);

        $parsedCert['extensions']['extendedKeyUsage'] = 'Digital Signature';

        $method = $this->getReflectionMethod('validateKeyUsage');

        $validator = $this->createRevocationValidator();

        $this->expectException(OcspKeyUsageException::class);
        $method->invoke($validator, $parsedCert);
    }

    public function testRevocationValidatorViaCrlPasses(): void
    {
        $validator = new RevocationValidator(
            $this->createMockConfig(file_get_contents(self::CRL_RESPONSE_BIN_PATH)),
            $this->getSigningSession()
        );

        $method = $this->getReflectionMethod('validateRevocationViaCrl');

        $method->invoke($validator, file_get_contents(self::SUBJECT_CERTIFICATE_FILE_PATH));

        $this->expectNotToPerformAssertions();
    }

    public function testRevocationValidatorViaCrlFails(): void
    {
        $validator = new RevocationValidator(
            $this->createMockConfig(file_get_contents(self::CRL_RESPONSE_BIN_PATH)),
            $this->getSigningSession()
        );

        $method = $this->getReflectionMethod('validateRevocationViaCrl');

        $this->expectException(CrlUrlMissingException::class);
        $method->invoke($validator, file_get_contents(self::INVALID_CERT_FILE_PATH));
    }
}
