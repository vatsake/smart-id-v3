<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Requests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Enums\CertificateLevel;
use Vatsake\SmartIdV3\Enums\HashAlgorithm;
use Vatsake\SmartIdV3\Requests\NotificationCertChoiceRequest;
use Vatsake\SmartIdV3\Requests\NotificationSigningRequest;
use Vatsake\SmartIdV3\Requests\DeviceLinkSigningRequest;

class RequestTest extends TestCase
{
    // Nonce trait tests
    public function testNonceCanBeSet(): void
    {
        $request = NotificationCertChoiceRequest::builder()
            ->withNonce('test-nonce')
            ->build();

        $this->assertEquals('test-nonce', $request->nonce);
    }

    public function testNonceThrowsExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce cannot be longer than 30 characters');

        NotificationCertChoiceRequest::builder()
            ->withNonce(str_repeat('a', 31))
            ->build();
    }

    public function testNonceAcceptsMaximumLength(): void
    {
        $nonce = str_repeat('a', 30);

        $request = NotificationCertChoiceRequest::builder()
            ->withNonce($nonce)
            ->build();

        $this->assertEquals($nonce, $request->nonce);
    }

    public function testEmptyNonceIsAllowed(): void
    {
        $request = NotificationCertChoiceRequest::builder()
            ->withNonce('')
            ->build();

        $this->assertEquals('', $request->nonce);
    }

    public function testToArrayExcludesNullFields(): void
    {
        $request = NotificationCertChoiceRequest::builder()->build();

        $array = $request->toArray();

        $this->assertArrayNotHasKey('nonce', $array);
        $this->assertArrayNotHasKey('certificateLevel', $array);
    }

    public function testRequestPropertiesCanBeSetToTrue(): void
    {
        $request = NotificationCertChoiceRequest::builder()
            ->withRequestProperties(true)
            ->build();

        $this->assertEquals(['shareMdClientIpAddress' => true], $request->requestProperties);
    }

    public function testAllCertificateLevelsWork(): void
    {
        foreach ([CertificateLevel::QUALIFIED, CertificateLevel::ADVANCED, CertificateLevel::QSCD] as $level) {
            $request = NotificationCertChoiceRequest::builder()
                ->withCertificateLevel($level)
                ->build();

            $this->assertEquals($level->value, $request->certificateLevel);
        }
    }

    // InitialCallbackUrl trait tests
    public function testCallbackUrlThrowsExceptionWhenInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Initial Callback URL is invalid');

        DeviceLinkSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions('Sign')
            ->withInitialCallbackUrl('http://example.com')
            ->build();
    }

    public function testCallbackUrlThrowsExceptionWhenHasFragment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Initial Callback URL is invalid');

        DeviceLinkSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions('Sign')
            ->withInitialCallbackUrl('https://example.com#fragment')
            ->build();
    }

    public function testCallbackUrlAcceptsValidUrl(): void
    {
        $url = 'https://example.com/callback';

        $request = DeviceLinkSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions('Sign')
            ->withInitialCallbackUrl($url)
            ->build();

        $this->assertEquals($url, $request->initialCallbackUrl);
    }

    // Interactions trait tests
    public function testInteractionsAreBase64Encoded(): void
    {
        $request = NotificationSigningRequest::builder()
            ->withData('data', HashAlgorithm::SHA_256)
            ->withInteractions('Test display text')
            ->build();

        $this->assertIsString($request->interactions);
        $decoded = base64_decode($request->interactions, true);
        $this->assertNotFalse($decoded);

        $interactions = json_decode($decoded, true);
        $this->assertIsArray($interactions);
        $this->assertNotEmpty($interactions);
    }

    public function testInteractionsThrowsExceptionWhenMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one interaction must be provided');

        NotificationSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->build();
    }

    public function testInteractionsThrowsExceptionWhenDisplayText60TooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('displayText60 exceeds maximum length of 60 characters');

        NotificationSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions(str_repeat('a', 61))
            ->build();
    }

    public function testInteractionsAcceptsDisplayText60WithMaxLength(): void
    {
        $text = str_repeat('a', 60);

        $request = NotificationSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions($text)
            ->build();

        $interactions = json_decode(base64_decode($request->interactions), true);
        $this->assertEquals($text, $interactions[0]['displayText60']);
    }

    public function testInteractionsThrowsExceptionWhenDisplayText200TooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('displayText200 exceeds maximum length of 200 characters');

        NotificationSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions(null, str_repeat('a', 201))
            ->build();
    }

    public function testInteractionsAcceptsDisplayText200WithMaxLength(): void
    {
        $text = str_repeat('a', 200);

        $request = NotificationSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions(null, $text)
            ->build();

        $interactions = json_decode(base64_decode($request->interactions), true);
        $this->assertEquals($text, $interactions[0]['displayText200']);
    }

    public function testInteractionsWithBothTypes(): void
    {
        $text60 = 'Short text';
        $text200 = 'Longer confirmation message';

        $request = NotificationSigningRequest::builder()
            ->withData('Test', HashAlgorithm::SHA_256)
            ->withInteractions($text60, $text200)
            ->build();

        $interactions = json_decode(base64_decode($request->interactions), true);

        $this->assertCount(2, $interactions);
        $this->assertEquals($text60, $interactions[0]['displayText60']);
        $this->assertEquals('displayTextAndPIN', $interactions[0]['type']);
        $this->assertEquals($text200, $interactions[1]['displayText200']);
        $this->assertEquals('confirmationMessage', $interactions[1]['type']);
    }

    public function testBuildThrowsExceptionWhenDataMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Mandatory parameter 'digest' is not set");

        NotificationSigningRequest::builder()
            ->withInteractions('Sign')
            ->build();
    }
}
