<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

use CurlySanders\JobApplicationTracker\Domain\User\PendingResume;

interface PendingResumeValidationRepository
{
    public function findPendingResume(string $userId, string $validationId): ?PendingResume;

    /** Returns the obsolete storage path, or null when delivery is stale or duplicated. */
    public function completePendingResumeValidation(string $userId, string $validationId, ResumeValidationOutcome $outcome): ?string;
}
