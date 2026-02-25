<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Session;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Config\SmartIdConfig;
use Vatsake\SmartIdV3\Constants\SmartIdBaseUrl;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Exceptions\Validation\SignatureException;
use Vatsake\SmartIdV3\Identity\SemanticsIdentifier;
use Vatsake\SmartIdV3\Requests\NotificationAuthRequest;
use Vatsake\SmartIdV3\Responses\Signature\AcspV2Signature;
use Vatsake\SmartIdV3\SmartId;
use Vatsake\SmartIdV3\Utils\RpChallenge;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

class AuthTest extends TestCase
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
            certificatePath: __DIR__ . '/../Resources/trusted-mixed-certs',
        );
        $this->smartId = new SmartId($this->config);
    }

    public function testNotificationAuthRequestDocumentWithPolling()
    {
        $rpChallenge = RpChallenge::generate();
        $request = NotificationAuthRequest::builder()->withInteractions(
            'Test authentication',
            'Test authentication.'
        )->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->notification()->authentication()->startDocument($request, 'PNOEE-40504040001-DEM0-Q');

        assertNotNull($session->getSessionId());
        assertSame($rpChallenge, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->withPolling(1000)->getAuthSession(5000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(AcspV2Signature::class, $result->signature);
        assertNull($result->deviceIpAddress);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(false)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();

        // DEMO signature validation fails
        $this->expectException(SignatureException::class);
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(false)
            ->withRevocationValidation(false)
            ->check();
    }

    public function testNotificationAuthRequestWithEtsi()
    {
        $rpChallenge = RpChallenge::generate();
        $identifier = SemanticsIdentifier::fromPersonalNumber('EE', '40504040001');
        $request = NotificationAuthRequest::builder()->withInteractions(
            'Test authentication',
            'Test authentication.'
        )->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->notification()->authentication()->startEtsi($request, $identifier);

        assertNotNull($session->getSessionId());
        assertSame($rpChallenge, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getAuthSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(AcspV2Signature::class, $result->signature);
        assertNull($result->deviceIpAddress);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(false)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();

        // DEMO signature validation fails
        $this->expectException(SignatureException::class);
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(false)
            ->withRevocationValidation(false)
            ->check();
    }

    // Mocking doesn't work
    /*
    public function testDeviceLinkAuthRequestWithDocument()
    {
        $rpChallenge = RpChallenge::generate();
        $request = AuthRequest::builder()->withInteractions(
            'Test authentication',
            'Test authentication.'
        )->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->deviceLink()->authentication()->startDocument($request, 'PNOEE-40404040009-MOCK-Q');

        $mockReq = MockRequest::builder()->withDocumentNumber('PNOEE-40404040009-MOCK-Q')
            ->withDeviceLink($session->getDeviceLink(DeviceLinkType::QR))
            ->withFlowType(FlowType::QR)
            ->build();
        $this->smartId->deviceLink()->mockDevice()->start($mockReq);

        assertNotNull($session->getSessionId());
        assertSame($rpChallenge, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getAuthSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(AcspV2Signature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(false)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();

        // DEMO signature validation fails
        $this->expectException(SignatureException::class);
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(false)
            ->withRevocationValidation(false)
            ->check();
    }
     */

    // Mocking doesn't work
    /*
    public function testDeviceLinkAuthRequestWithEtsi()
    {
        $rpChallenge = RpChallenge::generate();
        $identifier = SemanticsIdentifier::fromPersonalNumber('EE', '40404040009');
        $request = AuthRequest::builder()->withInteractions(
            'Test authentication',
            'Test authentication.'
        )->withRpChallenge($rpChallenge, HashAlgorithm::SHA_512)
            ->build();

        $session = $this->smartId->deviceLink()->authentication()->startEtsi($request, $identifier);

        $mockReq = MockRequest::builder()->withDocumentNumber('PNOEE-40404040009-MOCK-Q')
            ->withDeviceLink($session->getDeviceLink(DeviceLinkType::QR))
            ->withFlowType(FlowType::QR)
            ->build();
        $this->smartId->deviceLink()->mockDevice()->start($mockReq);

        assertNotNull($session->getSessionId());
        assertSame($rpChallenge, $session->getSignedData());
        assertSame($request->interactions, $session->getInteractions());
        assertEmpty($session->getInitialCallbackUrl(), '');

        $result = $this->smartId->session($session)->getAuthSession(60000);
        assertTrue($result->isSuccessful());
        assertInstanceOf(AcspV2Signature::class, $result->signature);
        assertNull($result->deviceIp);

        // Will throw if validation fails
        $result->validate()
            ->withSignatureValidation(false)
            ->withCertificateValidation(true)
            ->withRevocationValidation(true)
            ->check();

        // DEMO signature validation fails
        $this->expectException(SignatureException::class);
        $result->validate()
            ->withSignatureValidation(true)
            ->withCertificateValidation(false)
            ->withRevocationValidation(false)
            ->check();
    }
     */
}
