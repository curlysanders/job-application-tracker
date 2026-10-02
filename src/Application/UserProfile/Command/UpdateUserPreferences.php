<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

final readonly class UpdateUserPreferences
{
    public function __construct(
        public string $userId,
        public ?string $minimumPreferredSalary,
        public string $minimumPreferredSalaryCurrency,
        public ?int $maximumCommuteMinutes,
        public ?string $preferredTransportMode,
    ) {
    }
}
