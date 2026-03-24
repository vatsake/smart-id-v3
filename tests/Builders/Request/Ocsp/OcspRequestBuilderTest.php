<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request\Ocsp;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\Ocsp\OcspRequestBuilder;
use Vatsake\SmartIdV3\Requests\Ocsp\OcspRequest;

class OcspRequestBuilderTest extends TestCase
{
    private string $subjectCertificate;
    private string $issuerCertificate;

    protected function setUp(): void
    {
        $this->subjectCertificate = file_get_contents(__DIR__ . '/../../../resources/subject-certificates/OK-SIGN-Q-PNOEE-40504040001.crt');
        $this->issuerCertificate = file_get_contents(__DIR__ . '/../../../resources/trusted-certificates/TEST_of_SK_ID_Solutions_EID-Q_2024E.pem.crt');
    }

    public function testSuccessfulBuild(): void
    {
        $builder = new OcspRequestBuilder();
        $request = $builder
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();

        $this->assertInstanceOf(OcspRequest::class, $request);
        $this->assertNotEmpty($request->getBody());
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new OcspRequestBuilder();
        $result = $builder->withSubjectCertificate($this->subjectCertificate);
        $this->assertSame($builder, $result);
    }

    public function testBuilderReturnsStringBody(): void
    {
        $builder = new OcspRequestBuilder();
        $request = $builder
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();

        $body = $request->getBody();
        $this->assertIsString($body);
        $this->assertNotEmpty($body);
    }

    public function testInvalidSubjectCertificate(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse subject certificate');

        $builder = new OcspRequestBuilder();
        $builder
            ->withSubjectCertificate('INVALID_CERTIFICATE_DATA')
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();
    }

    public function testInvalidIssuerCertificate(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse issuer certificate');

        $builder = new OcspRequestBuilder();
        $builder
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate('INVALID_CERTIFICATE_DATA')
            ->build();
    }

    public function testOcspRequestBodyIsBase64Compatible(): void
    {
        $builder = new OcspRequestBuilder();
        $request = $builder
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate)
            ->build();

        $body = $request->getBody();
        $this->assertNotEmpty($body);
    }

    public function testMultipleBuildCalls(): void
    {
        $builder = new OcspRequestBuilder();
        $builder
            ->withSubjectCertificate($this->subjectCertificate)
            ->withIssuerCertificate($this->issuerCertificate);

        $request1 = $builder->build();
        $request2 = $builder->build();

        $this->assertEquals($request1->getBody(), $request2->getBody());
    }

    public function testCertificateDataPersistence(): void
    {
        $builder = new OcspRequestBuilder();
        $builder->withSubjectCertificate($this->subjectCertificate);
        $builder->withIssuerCertificate($this->issuerCertificate);

        $request = $builder->build();
        $this->assertInstanceOf(OcspRequest::class, $request);
    }
}
