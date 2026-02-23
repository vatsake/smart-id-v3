<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use OpenSSLCertificate;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Constants\SmartIdBaseUrl;
use Vatsake\SmartIdV3\Enums\DeviceLinkType;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\AuthRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;
use Vatsake\SmartIdV3\Requests\LinkedRequest;
use Vatsake\SmartIdV3\Requests\MockRequest;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Responses\AcspV2Signature;
use Vatsake\SmartIdV3\Responses\CertificateChoiceSignature;
use Vatsake\SmartIdV3\Responses\RawDigestSignature;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class SignTest extends TestCase
{
    protected SmartIdConfig $config;
    protected SmartId $smartId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new SmartIdConfig(
            baseUrl: SmartIdBaseUrl::DEMO,
            relyingPartyUUID: '00000000-0000-4000-8000-000000000000',
            relyingPartyName: 'DEMO',
            certificatePath: __DIR__ . '/../resources/trusted-mixed-certs',
        );
        $this->smartId = new SmartId($this->config);
    }

    public function testNotificationSignRequestWithDocument()
    {
        $data = 'raw data to sign';
        $request = NotificationSigningRequest::builder()
            ->withInteractions(
                'Test signing',
                'Test signing.'
            )->withData($data, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->notification()->signing()->startDocument($request, 'PNOEE-40504040001-DEM0-Q');

        assertNotNull($session->getSessionId());
        assertSame($data, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getSigningSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(RawDigestSignature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();
    }

    public function testNotificationSignRequestWithEtsi()
    {
        $data = 'raw data to sign';
        $identifier = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $request = NotificationSigningRequest::builder()
            ->withInteractions(
                'Test signing',
                'Test signing.'
            )->withData($data, HashAlgorithm::SHA_512)
            ->build();


        $session = $this->smartId->notification()->signing()->startEtsi($request, $identifier);

        assertNotNull($session->getSessionId());
        assertSame($data, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getSigningSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(RawDigestSignature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();
    }


    public function testGetSignCertificate()
    {
        $session = $this->smartId->getSigningCertificate('PNOEE-40504040001-DEM0-Q');
        assertSame('Ok', $session->getSubjectGN());
        assertSame('Test', $session->getSubjectSN());
        assertSame('Ok Test', $session->getSubjectName());
        assertInstanceOf(SemanticsIdentifier::class, $session->getSubjectIdentifier());
        assertInstanceOf(OpenSSLCertificate::class, $session->getX509Resource());
    }

    // Linked doesn't work
    /*
    public function testNotificationLinkedSignRequest()
    {
        $identifier = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $request = NotificationCertChoiceRequest::builder()->build();
        $session = $this->smartId->notification()->signing()->startCertChoice($request, $identifier);
        $id = $session->getSessionId();

        assertNotNull($session->getSessionId());
        assertEmpty($session->getSignedData());
        assertEmpty($session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl());

        $result = $this->smartId->session($session)->getCertChoiceSession(60000);

        assertTrue($result->isSuccessful());
        assertInstanceOf(CertificateChoiceSignature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate();

        $data = 'raw data to sign';
        $request = LinkedRequest::builder()
            ->withLinkedSessionId($id)
            ->withInteractions(
                'Test signing',
                'Test signing.'
            )->withData($data, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->notification()->signing()->startLinkedSigning($request, $result->identifier);

        assertNotNull($session->getSessionId());
        assertSame($data, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');
    }
    */

    // Mocking doesn't work
    /*
    public function testDeviceLinkLinkedSignRequest()
    {
        $request = DeviceLinkCertChoiceRequest::builder()->build();
        $session = $this->smartId->deviceLink()->signing()->startAnonymousCertChoice($request);
        $id = $session->getSessionId();

        assertNotNull($session->getSessionId());
        assertEmpty($session->getSignedData());
        assertEmpty($session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl());

        $mockReq = MockRequest::builder()->withDocumentNumber('PNOEE-40404040009-MOCK-Q')
            ->withDeviceLink($session->getDeviceLink(DeviceLinkType::QR))
            ->withFlowType(FlowType::QR)
            ->build();
        $this->smartId->deviceLink()->mockDevice()->start($mockReq);

        $result = $this->smartId->session($session)->getCertChoiceSession(60000);
        dd($result);

        assertTrue($result->isSuccessful());
        assertInstanceOf(CertificateChoiceSignature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate();

        $data = 'raw data to sign';
        $request = LinkedRequest::builder()
            ->withLinkedSessionId($id)
            ->withInteractions(
                'Test signing',
                'Test signing.'
            )->withData($data, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->deviceLink()->signing()->startLinkedSigning($request, $result->identifier);

        assertNotNull($session->getSessionId());
        assertSame($data, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');
    }
    */

    // Mocking doesn't work
    /*
    public function testDeviceLinkSignRequestWithDocument()
    {
        $data = 'raw data to sign';
        $request = DeviceLinkSigningRequest::builder()
            ->withInteractions(
                'Test signing',
                'Test signing.'
            )->withData($data, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->deviceLink()->signing()->startDocument($request, 'PNOEE-40404040009-MOCK-Q');

        $mockReq = MockRequest::builder()->withDocumentNumber('PNOEE-40404040009-MOCK-Q')
            ->withDeviceLink($session->getDeviceLink(DeviceLinkType::QR))
            ->withFlowType(FlowType::QR)
            ->build();
        $this->smartId->deviceLink()->mockDevice()->start($mockReq);

        assertNotNull($session->getSessionId());
        assertSame(base64_encode(hash('sha512', $data, true)), $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getSigningSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(RawDigestSignature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();
    }
*/

    // Mocking doesn't work
    /*
    public function testDeviceLinkSignRequestWithEtsi()
    {
        $data = 'raw data to sign';
        $identifier = SemanticsIdentifier::fromPersonalNumber('EE', '40404040009');
        $request = DeviceLinkSigningRequest::builder()
            ->withInteractions(
                'Test signing',
                'Test signing.'
            )->withData($data, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->deviceLink()->signing()->startEtsi($request, $identifier);

        $mockReq = MockRequest::builder()->withDocumentNumber('PNOEE-40404040009-MOCK-Q')
            ->withDeviceLink($session->getDeviceLink(DeviceLinkType::QR))
            ->withFlowType(FlowType::QR)
            ->build();
        $this->smartId->deviceLink()->mockDevice()->start($mockReq);

        assertNotNull($session->getSessionId());
        assertSame(base64_encode(hash('sha512', $data, true)), $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getSigningSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(RawDigestSignature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();
    }
    */
}
