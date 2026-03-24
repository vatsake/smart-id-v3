<?php

namespace Vatsake\SmartIdV3\Tests\Rest;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Responses\Certificate;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Tests\MockConfigTrait;

class SigningCertificateTest extends TestCase
{
    use MockConfigTrait;

    private const CERTIFICATE_RESPONSE_FILE_PATH = __DIR__ . '/../resources/responses/signing/certificate-response.json';

    public function testSuccessfulSigningWithDocumentRequestAndResponse()
    {
        $response = json_decode(file_get_contents(self::CERTIFICATE_RESPONSE_FILE_PATH), true);
        $smartId = new SmartId($this->createMockConfig($response));

        $cert = $smartId->getSigningCertificate('PNOEE-40504040001-DEM0-Q');

        $this->assertInstanceOf(Certificate::class, $cert);
        $this->assertEquals(CertificateLevel::QUALIFIED, $cert->certificateLevel);
        $this->assertEquals('40504040001', $cert->getSubjectIdentifier()->identifier);
    }
}
