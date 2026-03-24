<?php

namespace Vatsake\SmartIdV3\Tests\Validators;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Enums\SmartIdEnv;
use Vatsake\SmartIdV3\Exceptions\Validation\CertificateChainException;
use Vatsake\SmartIdV3\Validators\CertificateChainValidator;

class CertificateChainValidatorTest extends TestCase
{
    private const VALID_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/OK-SIGN-Q-PNOEE-40504040001.crt';
    private const EXPIRED_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/FAKE-TEST-EXPIRED-20991231.crt';
    private const INVALID_CERTIFICATE_PATH = __DIR__ . '/../resources/subject-certificates/INVALID-CERTIFICATE.crt';

    private function createConfig($overwrite = [])
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        return new SmartIdConfig(
            relyingPartyName: 'DEMO',
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            env: SmartIdEnv::DEMO,
            certificatePath: __DIR__ . '/../resources/trusted-certificates',
            cache: $cache,
        );
    }

    public function testLoadCAs()
    {
        new CertificateChainValidator($this->createConfig());
        $this->expectNotToPerformAssertions();
    }

    public function testValidateValidCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = file_get_contents(self::VALID_CERTIFICATE_PATH);
        $validator->validateChain($pem);
        $this->expectNotToPerformAssertions();
    }

    public function testGetSubjectsParentCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = file_get_contents(self::VALID_CERTIFICATE_PATH);
        $issuer = $validator->getIssuerCertificate($pem);
        $this->assertNotNull($issuer);
    }

    public function testGetInvalidSubjectsPemParentCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = base64_encode('invalid_cert');
        $this->expectException(CertificateChainException::class);
        $validator->getIssuerCertificate($pem);
    }

    public function testValidateUntrustedCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = file_get_contents(self::INVALID_CERTIFICATE_PATH);
        $this->expectException(CertificateChainException::class);
        $validator->getIssuerCertificate($pem);
    }

    public function testValidateInvalidCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = file_get_contents(self::INVALID_CERTIFICATE_PATH);
        $this->expectException(CertificateChainException::class);
        $validator->validateChain($pem);
    }

    public function testValidateExpiredCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = file_get_contents(self::EXPIRED_CERTIFICATE_PATH);
        $this->expectException(CertificateChainException::class);
        $validator->validateChain($pem);
    }

    public function testValidateValidAuthCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = file_get_contents(self::VALID_CERTIFICATE_PATH);
        $validator->validateChain($pem);
        $this->expectNotToPerformAssertions();
    }

    public function testValidateInvalidPemCertificate()
    {
        $validator = new CertificateChainValidator($this->createConfig());
        $pem = base64_encode('invalid_cert');
        $this->expectException(CertificateChainException::class);
        $validator->validateChain($pem);
    }
}
