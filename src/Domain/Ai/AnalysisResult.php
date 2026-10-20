<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;

final class AnalysisResult
{
    private(set) Identifier $id;

    private(set) \DateTimeImmutable $createdAt;

    public function __construct(
        public readonly AnalysisRequest $request,
        public readonly string $ciphertext,
        public readonly string $fingerprint,
    ) {
        if ('' === $ciphertext || 1 !== preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
            throw new \InvalidArgumentException('An analysis result requires authenticated content and a SHA-256 fingerprint.');
        }

        $this->id = Identifier::v7();
        $this->createdAt = new \DateTimeImmutable();
    }
}
