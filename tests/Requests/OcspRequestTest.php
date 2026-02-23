<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests\Ocsp;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\Ocsp\OcspRequestBuilder;
use Vatsake\SmartIdV3\Requests\Ocsp\OcspRequest;

class OcspRequestTest extends TestCase
{
    private string $subjectCertificate;
    private string $issuerCertificate;

    protected function setUp(): void
    {
        parent::setUp();

        // Load test certificates from test-resources
        $this->subjectCertificate = $this->loadCertificateAsBase64(__DIR__ . '/../../test-resources/TEST_of_SK_ID_Solutions_EID-Q_2024E.pem.crt');
        $this->issuerCertificate = $this->loadCertificateAsBase64(__DIR__ . '/../../test-resources/TEST_SK_ROOT_G1_2021E.pem.crt');
    }

    private function loadCertificateAsBase64(string $path): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to load certificate from $path");
        }

        // Remove PEM headers and whitespace
        $cert = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '', $content);
        if ($cert === null) {
            throw new \RuntimeException("Failed to parse certificate from $path");
        }

        return $cert;
    }

    public function testBuilderCreatesOcspRequest(): void
    {
        $request = OcspRequest::builder()
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();
        $this->assertInstanceOf(OcspRequest::class, $request);
    }

    public function testOcspRequestHasBody(): void
    {
        $request = OcspRequest::builder()
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();

        $body = $request->getBody();
        $this->assertIsString($body);
        $this->assertNotEmpty($body);
    }

    public function testOcspRequestBodyIsEncoded(): void
    {
        $request = OcspRequest::builder()
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();

        $body = $request->getBody();
        // OCSP request body should start with SEQUENCE tag (0x30)
        // or be binary encoded
        $this->assertIsString($body);
        $this->assertTrue(strlen($body) > 0);
    }

    public function testBuildThrowsExceptionWithInvalidSubjectCertificate(): void
    {
        $this->expectException(\RuntimeException::class);

        OcspRequest::builder()
            ->withSubjectCertificate('invalid-base64-cert')
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();
    }

    public function testBuildThrowsExceptionWithInvalidIssuerCertificate(): void
    {
        $this->expectException(\RuntimeException::class);

        OcspRequest::builder()
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate('invalid-base64-cert')
            ->build();
    }

    public function testBuilderRequiresBothCertificates(): void
    {
        $this->expectException(\Throwable::class);

        OcspRequest::builder()
            ->withSubjectCertificate($this->subjectCertificate)
            ->build();
    }
}
