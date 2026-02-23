<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Builders\Request;

use Vatsake\SmartIdV3\Enums\CertificateLevel;

abstract class RequestBuilder
{
    protected ?CertificateLevel $certificateLevel = null;
    protected array $requestProperties = [
        'shareMdClientIpAddress' => false
    ];

    public function withRequestProperties(bool $shareMdClientIpAddress)
    {
        $this->requestProperties['shareMdClientIpAddress'] = $shareMdClientIpAddress;
        return $this;
    }

    public function withCertificateLevel(CertificateLevel $level)
    {
        $this->certificateLevel = $level;
        return $this;
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

    protected abstract function mandatoryParameters(): array;

    protected abstract function validate(): void;

    public abstract function build();
}
