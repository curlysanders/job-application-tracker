<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final class AnalysisRequest implements RecordsEvents
{
    use RecordsDomainEvents;

    private(set) Identifier $id;

    private(set) int $version = 1;

    private(set) AnalysisState $state = AnalysisState::Pending;

    private(set) ?string $failureCode = null;

    private(set) ?AnalysisResult $result = null;

    private(set) \DateTimeImmutable $createdAt;

    private(set) \DateTimeImmutable $updatedAt;

    public function __construct(
        public readonly User $user,
        public readonly AnalysisKind $kind,
        private(set) string $inputCiphertext,
        private(set) string $inputFingerprint,
        private(set) string $provider,
        private(set) string $model,
        private(set) \DateTimeImmutable $expiresAt,
    ) {
        $this->id = Identifier::v7();
        $this->assertInput();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->recordRequested();
    }

    public function replaceInput(
        string $ciphertext,
        string $fingerprint,
        string $provider,
        string $model,
        \DateTimeImmutable $expiresAt,
    ): void {
        $this->inputCiphertext = $ciphertext;
        $this->inputFingerprint = $fingerprint;
        $this->provider = $provider;
        $this->model = $model;
        $this->expiresAt = $expiresAt;
        $this->result = null;
        $this->state = AnalysisState::Pending;
        $this->failureCode = null;
        ++$this->version;
        $this->updatedAt = new \DateTimeImmutable();
        $this->assertInput();
        $this->recordRequested();
    }

    public function isCurrentPending(string $fingerprint): bool
    {
        return AnalysisState::Pending === $this->state && hash_equals($this->inputFingerprint, $fingerprint) && $this->expiresAt > new \DateTimeImmutable();
    }

    public function complete(AnalysisResult $result): void
    {
        if (!$this->isCurrentPending($this->inputFingerprint) || !$result->request->id->equals($this->id)) {
            throw new \LogicException('Only the current pending analysis request can be completed.');
        }

        $this->result = $result;
        $this->state = AnalysisState::Completed;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function fail(string $fingerprint): void
    {
        if (!$this->isCurrentPending($fingerprint)) {
            return;
        }

        $this->state = AnalysisState::Failed;
        $this->failureCode = 'provider_failed';
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function recordRequested(): void
    {
        $this->recordEvent(new AnalysisRequested($this->id, ['inputFingerprint' => $this->inputFingerprint]));
    }

    private function assertInput(): void
    {
        if ('' === $this->inputCiphertext || 1 !== preg_match('/^[a-f0-9]{64}$/', $this->inputFingerprint) || '' === trim($this->provider) || '' === trim($this->model)) {
            throw new \InvalidArgumentException('An analysis request requires encrypted input, a SHA-256 fingerprint, and provider metadata.');
        }
    }
}
