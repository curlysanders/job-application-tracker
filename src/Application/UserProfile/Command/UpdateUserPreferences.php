<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;

final readonly class UpdateUserPreferences
{
    public function __construct(
        public int $userId,
        public ?string $minimumPreferredSalary,
        public ?int $maximumCommuteMinutes,
        public ?PreferredTransportMode $preferredTransportMode,
    ) {
    }
}
