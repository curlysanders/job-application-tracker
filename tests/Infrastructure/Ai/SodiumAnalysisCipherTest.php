<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Infrastructure\Ai\SodiumAnalysisCipher;
use PHPUnit\Framework\TestCase;

final class SodiumAnalysisCipherTest extends TestCase
{
    private const string KEY = 'MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=';

    public function testEncryptsWithAuthenticatedRandomizedCiphertextAndStableFingerprint(): void
    {
        $cipher = new SodiumAnalysisCipher(self::KEY);

        $first = $cipher->encrypt('Sensitive vacancy text');
        $second = $cipher->encrypt('Sensitive vacancy text');

        self::assertNotSame($first, $second);
        self::assertSame('Sensitive vacancy text', $cipher->decrypt($first));
        self::assertSame($cipher->fingerprint('Sensitive vacancy text'), $cipher->fingerprint('Sensitive vacancy text'));
        self::assertNotSame($cipher->fingerprint('Sensitive vacancy text'), $cipher->fingerprint('Changed vacancy text'));
    }

    public function testRejectsInvalidKeyAndTamperedCiphertext(): void
    {
        $this->expectException(\LogicException::class);
        new SodiumAnalysisCipher(base64_encode('short'));
    }

    public function testRejectsMalformedAndTamperedCiphertext(): void
    {
        $cipher = new SodiumAnalysisCipher(self::KEY);
        $ciphertext = $cipher->encrypt('Sensitive vacancy text');

        try {
            $cipher->decrypt('not-base64');
            self::fail('Expected malformed ciphertext to be rejected.');
        } catch (\InvalidArgumentException) {
        }

        $this->expectException(\InvalidArgumentException::class);
        $cipher->decrypt(substr($ciphertext, 0, -2).'xx');
    }
}
