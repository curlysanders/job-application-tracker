<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;

interface VacancyAuthoringDraftRepository
{
    public function findOwnedBy(
        string $draftId,
        string $userId,
    ): ?VacancyAuthoringDraft;

    public function save(VacancyAuthoringDraft $draft): void;

    public function delete(VacancyAuthoringDraft $draft): void;

    public function purgeExpired(\DateTimeImmutable $now): int;
}
