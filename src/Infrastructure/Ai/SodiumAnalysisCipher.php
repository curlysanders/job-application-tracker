<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(AnalysisCipher::class)]
final readonly class SodiumAnalysisCipher implements AnalysisCipher
{
    private string $encryptionKey;

    private string $fingerprintKey;

    public function __construct(
        #[Autowire('%env(string:AI_ANALYSIS_ENCRYPTION_KEY)%')]
        string $masterKey,
    ) {
        $masterKey = base64_decode($masterKey, true);
        if (false === $masterKey || SODIUM_CRYPTO_SECRETBOX_KEYBYTES !== strlen($masterKey)) {
            throw new \LogicException(sprintf('AI_ANALYSIS_ENCRYPTION_KEY must be a base64-encoded %d-byte key.', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
        }

        $this->encryptionKey = sodium_crypto_generichash('job-application-tracker/ai-analysis/encryption', $masterKey, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $this->fingerprintKey = sodium_crypto_generichash('job-application-tracker/ai-analysis/fingerprint', $masterKey, SODIUM_CRYPTO_AUTH_KEYBYTES);
    }

    public function encrypt(
        #[\SensitiveParameter]
        string $plaintext,
    ): string {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($plaintext, $nonce, $this->encryptionKey));
    }

    public function decrypt(
        #[\SensitiveParameter]
        string $ciphertext,
    ): string {
        $payload = base64_decode($ciphertext, true);
        if (false === $payload || strlen($payload) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \InvalidArgumentException('The analysis ciphertext is malformed.');
        }

        $plaintext = sodium_crypto_secretbox_open(
            substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            $this->encryptionKey,
        );
        if (false === $plaintext) {
            throw new \InvalidArgumentException('The analysis ciphertext authentication failed.');
        }

        return $plaintext;
    }

    public function fingerprint(
        #[\SensitiveParameter]
        string $plaintext,
    ): string {
        return hash_hmac('sha256', $plaintext, $this->fingerprintKey);
    }
}
