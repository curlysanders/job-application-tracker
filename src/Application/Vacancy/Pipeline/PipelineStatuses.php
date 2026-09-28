<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;

final class PipelineStatuses
{
    /** @return list<VacancyStatus> */
    public static function all(): array
    {
        return [
            VacancyStatus::Bookmarked,
            VacancyStatus::Applying,
            VacancyStatus::Applied,
            VacancyStatus::Interviewing,
            VacancyStatus::Negotiating,
            VacancyStatus::Accepted,
        ];
    }

    public static function fromFilter(?string $value): ?VacancyStatus
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $status = VacancyStatus::tryFrom($value);
        if (null === $status || !in_array($status, self::all(), true)) {
            throw new \InvalidArgumentException('The selected vacancy status is not part of the pipeline.');
        }

        return $status;
    }
}
