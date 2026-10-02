<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder;

interface DueVacancyReminderRepository
{
    /** @return list<DueVacancyReminder> */
    public function forUserDueBefore(string $userId, \DateTimeImmutable $endExclusive): array;
}
