<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;

interface AnalysisRequestRepository
{
    public function findOwnedBy(
        string $requestId,
        string $userId,
    ): ?AnalysisRequest;

    public function find(string $requestId): ?AnalysisRequest;

    public function save(AnalysisRequest $request): void;

    public function complete(
        string $requestId,
        string $inputFingerprint,
        string $resultCiphertext,
        string $resultFingerprint,
    ): bool;

    public function fail(
        string $requestId,
        string $inputFingerprint,
    ): void;

    public function purgeExpired(\DateTimeImmutable $now): int;
}
