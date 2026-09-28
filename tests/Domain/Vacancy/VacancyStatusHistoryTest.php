<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
use PHPUnit\Framework\TestCase;

final class VacancyStatusHistoryTest extends TestCase
{
    public function testStoresTypedStatusesAndNormalizesAnOptionalNote(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $transitionedAt = new \DateTimeImmutable('2026-09-28 12:00:00');
        $history = new VacancyStatusHistory($vacancy, VacancyStatus::Bookmarked, VacancyStatus::Applying, '  First screening scheduled.  ', $transitionedAt);

        self::assertSame($vacancy, $history->getVacancy());
        self::assertSame(VacancyStatus::Bookmarked, $history->getFromStatus());
        self::assertSame(VacancyStatus::Applying, $history->getToStatus());
        self::assertSame('First screening scheduled.', $history->getNotes());
        self::assertSame($transitionedAt, $history->getTransitionedAt());
    }

    public function testConvertsABlankOptionalNoteToNull(): void
    {
        $history = new VacancyStatusHistory(
            new Vacancy(new User(), 'Senior PHP Developer'),
            VacancyStatus::Bookmarked,
            VacancyStatus::Applying,
            ' ',
        );

        self::assertNull($history->getNotes());
    }
}
