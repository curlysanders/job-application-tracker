<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final class VacancyAuthoringDraft
{
    private(set) Identifier $id;

    private(set) \DateTimeImmutable $createdAt;

    public function __construct(
        public readonly User $user,
        public readonly string $ciphertext,
        public readonly \DateTimeImmutable $expiresAt,
    ) {
        if ('' === $ciphertext) {
            throw new \InvalidArgumentException('A vacancy authoring draft requires encrypted content.');
        }

        $this->id = Identifier::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function isExpired(\DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }
}
