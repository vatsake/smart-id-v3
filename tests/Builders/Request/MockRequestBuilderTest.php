<?php

namespace Vatsake\SmartIdV3\Tests\Builders\Request;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Builders\Request\MockRequestBuilder;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Requests\MockRequest;

class MockRequestBuilderTest extends TestCase
{
    public function testSuccessfulBuildWithQRFlow(): void
    {
        $builder = new MockRequestBuilder();
        $request = $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::QR)
            ->build();

        $this->assertInstanceOf(MockRequest::class, $request);
        $this->assertEquals('PNO-EE-40504040001', $request->documentNumber);
        $this->assertEquals('http://smartid.app:8080/paths', $request->deviceLink);
        $this->assertEquals('QR', $request->flowType);
    }

    public function testSuccessfulBuildWithWeb2AppFlow(): void
    {
        $builder = new MockRequestBuilder();
        $request = $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::WEB_TO_APP)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertInstanceOf(MockRequest::class, $request);
        $this->assertEquals('https://example.com/callback', $request->initialCallbackUrl);
    }

    public function testSuccessfulBuildWithApp2AppFlow(): void
    {
        $builder = new MockRequestBuilder();
        $request = $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::APP_TO_APP)
            ->withInitialCallbackUrl('https://example.com/callback')
            ->build();

        $this->assertInstanceOf(MockRequest::class, $request);
        $this->assertEquals(FlowType::APP_TO_APP->value, $request->flowType);
    }

    public function testBuilderWithOptionalBrowserCookie(): void
    {
        $builder = new MockRequestBuilder();
        $request = $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::QR)
            ->withBrowserCookie('sessionId=abc123')
            ->build();

        $this->assertEquals('sessionId=abc123', $request->browserCookie);
    }

    public function testBuilderReturnsSelfForChaining(): void
    {
        $builder = new MockRequestBuilder();
        $result = $builder->withDocumentNumber('PNO-EE-40504040001');
        $this->assertSame($builder, $result);
    }

    public function testMissingDocumentNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'documentNumber' is not set.");

        $builder = new MockRequestBuilder();
        $builder
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::QR)
            ->build();
    }

    public function testMissingDeviceLink(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'deviceLink' is not set.");

        $builder = new MockRequestBuilder();
        $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withFlowType(FlowType::QR)
            ->build();
    }

    public function testMissingFlowType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'flowType' is not set.");

        $builder = new MockRequestBuilder();
        $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->build();
    }

    public function testInvalidFlowTypeNotification(): void
    {
        $this->expectException(\Error::class);

        $builder = new MockRequestBuilder();
        $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::NOTIFICATION)
            ->build();
    }

    public function testAllValidFlowTypes(): void
    {
        $validFlowTypes = [FlowType::QR, FlowType::WEB_TO_APP, FlowType::APP_TO_APP];

        foreach ($validFlowTypes as $flowType) {
            $builder = new MockRequestBuilder();
            $request = $builder
                ->withDocumentNumber('PNO-EE-40504040001')
                ->withDeviceLink('http://smartid.app:8080/paths')
                ->withFlowType($flowType)
                ->build();

            $this->assertEquals($flowType->value, $request->flowType);
        }
    }

    public function testWeb2AppRequiresCallbackUrl(): void
    {
        $builder = new MockRequestBuilder();
        $request = $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::QR)
            ->build();

        $this->assertNull($request->initialCallbackUrl);
    }

    public function testOptionalFieldsAsArray(): void
    {
        $builder = new MockRequestBuilder();
        $request = $builder
            ->withDocumentNumber('PNO-EE-40504040001')
            ->withDeviceLink('http://smartid.app:8080/paths')
            ->withFlowType(FlowType::QR)
            ->withBrowserCookie('test_cookie')
            ->build();

        $this->assertNotNull($request->documentNumber);
        $this->assertNotNull($request->deviceLink);
        $this->assertNotNull($request->flowType);
    }
}
