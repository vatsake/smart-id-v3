<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Builders\Request\Concerns\InitialCallbackUrl;
use Vatsake\SmartIdV3\Builders\Request\Concerns\OptionalFields;
use Vatsake\SmartIdV3\Enums\FlowType;
use Vatsake\SmartIdV3\Requests\MockRequest;

class MockRequestBuilder
{
    use OptionalFields;
    use InitialCallbackUrl;

    protected ?string $documentNumber = null;
    protected ?string $deviceLink = null;
    protected ?FlowType $flowType = null;
    protected ?string $browserCookie = null;

    public function withDocumentNumber(string $documentNumber): self
    {
        $this->documentNumber = $documentNumber;
        return $this;
    }

    public function withDeviceLink(string $deviceLink): self
    {
        $this->deviceLink = $deviceLink;
        return $this;
    }

    /**
     * @param FlowType $flowType - Supported values: 'Web2App', 'App2App', 'QR'
     */
    public function withFlowType(FlowType $flowType): self
    {
        $this->flowType = $flowType;
        return $this;
    }

    public function withBrowserCookie(string $browserCookie): self
    {
        $this->browserCookie = $browserCookie;
        return $this;
    }

    protected function mandatoryParameters(): array
    {
        return ['documentNumber', 'deviceLink', 'flowType'];
    }

    private function validateFlowType(): void
    {
        if ($this->flowType === FlowType::NOTIFICATION) {
            throw new \InvalidArgumentException("Invalid flow type: {$this->flowType}.");
        }
    }

    protected function validateMandatoryParameters(): void
    {
        $mandatoryParams = $this->mandatoryParameters();

        foreach ($mandatoryParams as $param) {
            if (!isset($this->$param)) {
                throw new \InvalidArgumentException("Mandatory parameter '{$param}' is not set.");
            }
        }
    }

    protected function validate(): void
    {
        $this->validateMandatoryParameters();
        $this->validateInitialCallbackUrl();
        $this->validateFlowType();
    }

    /**
     * Mandatory params:
     * - documentNumber - use withDocumentNumber()
     * - deviceLink - use withDeviceLink()
     * - flowType - use withFlowType()
     *
     * Mandatory if flowType is Web2App/App2App:
     * - initialCallbackUrl - use withInitialCallbackUrl()
     */
    public function build()
    {
        $this->validate();

        $data = [
            'documentNumber' => $this->documentNumber,
            'deviceLink' => $this->deviceLink,
            'flowType' => $this->flowType->value,
        ];

        $data = $this->addOptionalFields($data, [
            'browserCookie',
            'initialCallbackUrl',
        ]);

        return new MockRequest($data);
    }
}
