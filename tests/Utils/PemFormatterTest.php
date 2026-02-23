<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Utils\PemFormatter;

class PemFormatterTest extends TestCase
{
    private const SAMPLE_CERT_BASE64 = 'MIIBkTCB+wIJAKHHCgVZU73BMA0GCSqGSIb3DQEBBQUAMA0xCzAJBgNVBAYTAlVTMB4XDTE0MDEwMTAwMDAwMFoXDTE1MDEwMTAwMDAwMFowDTELMAkGA1UE';

    public function testAddPemHeadersAddsCorrectHeaders(): void
    {
        $cert = self::SAMPLE_CERT_BASE64;
        $result = PemFormatter::addPemHeaders($cert);

        $this->assertStringStartsWith("-----BEGIN CERTIFICATE-----\n", $result);
        $this->assertStringEndsWith("-----END CERTIFICATE-----\n", $result);
    }

    public function testAddPemHeadersChunksTo64Characters(): void
    {
        $cert = str_repeat('A', 128);
        $result = PemFormatter::addPemHeaders($cert);

        $lines = explode("\n", trim($result));

        array_shift($lines); // Remove BEGIN CERTIFICATE
        array_pop($lines);   // Remove END CERTIFICATE

        // Each line should be max 64 characters
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(64, strlen($line));
        }
    }

    public function testStripPemHeadersRemovesHeaders(): void
    {
        $pem = "-----BEGIN CERTIFICATE-----\n" .
            self::SAMPLE_CERT_BASE64 . "\n" .
            "-----END CERTIFICATE-----\n";

        $result = PemFormatter::stripPemHeaders($pem);

        $this->assertStringNotContainsString("-----BEGIN CERTIFICATE-----", $result);
        $this->assertStringNotContainsString("-----END CERTIFICATE-----", $result);
    }

    public function testStripPemHeadersRemovesNewlines(): void
    {
        $pem = "-----BEGIN CERTIFICATE-----\n" .
            "Line1\n" .
            "Line2\n" .
            "-----END CERTIFICATE-----\n";

        $result = PemFormatter::stripPemHeaders($pem);

        $this->assertStringNotContainsString("\n", $result);
        $this->assertStringNotContainsString("\r", $result);
    }

    public function testStripPemHeadersHandlesCarriageReturns(): void
    {
        $pem = "-----BEGIN CERTIFICATE-----\r\n" .
            self::SAMPLE_CERT_BASE64 . "\r\n" .
            "-----END CERTIFICATE-----\r\n";

        $result = PemFormatter::stripPemHeaders($pem);

        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testAddPemHeadersWorksWithAlreadyFormattedCert(): void
    {
        $pem = "-----BEGIN CERTIFICATE-----\n" .
            self::SAMPLE_CERT_BASE64 . "\n" .
            "-----END CERTIFICATE-----\n";

        $result = PemFormatter::addPemHeaders($pem);

        // Should strip existing headers and re-add them
        $this->assertStringStartsWith("-----BEGIN CERTIFICATE-----\n", $result);
        $this->assertStringEndsWith("-----END CERTIFICATE-----\n", $result);

        // Should not have duplicate headers
        $headerCount = substr_count($result, "-----BEGIN CERTIFICATE-----");
        $footerCount = substr_count($result, "-----END CERTIFICATE-----");

        $this->assertEquals(1, $headerCount);
        $this->assertEquals(1, $footerCount);
    }

    public function testAddAndStripAreReversible(): void
    {
        $original = self::SAMPLE_CERT_BASE64;

        $withHeaders = PemFormatter::addPemHeaders($original);
        $stripped = PemFormatter::stripPemHeaders($withHeaders);

        $this->assertEquals($original, $stripped);
    }

    public function testStripPemHeadersWithJustBase64(): void
    {
        $cert = self::SAMPLE_CERT_BASE64;
        $result = PemFormatter::stripPemHeaders($cert);

        // Should return the same value if no headers present
        $this->assertEquals($cert, $result);
    }

    public function testAddPemHeadersWithEmptyString(): void
    {
        $result = PemFormatter::addPemHeaders('');

        $this->assertStringStartsWith("-----BEGIN CERTIFICATE-----\n", $result);
        $this->assertStringEndsWith("-----END CERTIFICATE-----\n", $result);
    }

    public function testPemFormatWithRealWorldExample(): void
    {
        $realCert = 'MIIBkTCB+wIJAKHHCgVZU73BMA0GCSqGSIb3DQEBBQUAMA0xCzAJBgNVBAYTAlVTMB4XDTE0MDEwMTAwMDAwMFoXDTE1MDEwMTAwMDAwMFowDTELMAkGA1UEBhMCVVMwXDANBgkqhkiG9w0BAQEFAANLADBIAkEAryQICCl6NZ5gDKrnSztO3Hy8PEUcuyvg/ikC+VcIo2bL52uDoUctfJPl5W/TFAA9';

        $pem = PemFormatter::addPemHeaders($realCert);
        $lines = explode("\n", trim($pem));

        $this->assertEquals("-----BEGIN CERTIFICATE-----", $lines[0]);
        $this->assertEquals("-----END CERTIFICATE-----", $lines[count($lines) - 1]);
    }
}
