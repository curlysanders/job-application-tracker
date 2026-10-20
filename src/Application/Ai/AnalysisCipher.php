<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

interface AnalysisCipher
{
    public function encrypt(
        #[\SensitiveParameter]
        string $plaintext,
    ): string;

    public function decrypt(
        #[\SensitiveParameter]
        string $ciphertext,
    ): string;

    public function fingerprint(
        #[\SensitiveParameter]
        string $plaintext,
    ): string;
}
