<?php

declare(strict_types=1);

namespace Vatsake\SmartIdV3\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Vatsake\SmartIdV3\Utils\RpChallenge;

class RpChallengeTest extends TestCase
{
    public function testGenerateReturnsBase64String(): void
    {
        $challenge = RpChallenge::generate();

        $this->assertIsString($challenge);
        $this->assertNotEmpty($challenge);

        $decoded = base64_decode($challenge, true);
        $this->assertNotFalse($decoded, 'Generated challenge should be valid base64');
    }

    public function testGenerateReturns64Bytes(): void
    {
        $challenge = RpChallenge::generate();
        $decoded = base64_decode($challenge);

        $this->assertEquals(64, strlen($decoded), 'Challenge should be 64 bytes when decoded');
    }

    public function testGenerateProducesUniqueValues(): void
    {
        $challenge1 = RpChallenge::generate();
        $challenge2 = RpChallenge::generate();

        $this->assertNotEquals($challenge1, $challenge2, 'Each challenge should be unique');
    }
}
