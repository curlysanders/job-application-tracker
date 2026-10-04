<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\RecordVacancyStatusHistory;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusHistoryRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusTransitioned;
use PHPUnit\Framework\TestCase;

final class RecordVacancyStatusTransitionTest extends TestCase
{
    public function testRecordsTheOutboxTransitionAndOptionalNote(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $vacancy->setStatus(VacancyStatus::Applying);
        $vacancy->recordStatusTransitioned(VacancyStatus::Bookmarked, 'start_applying', ' First screening scheduled. ');
        $event = $vacancy->getRecordedEvents()[0];
        self::assertInstanceOf(VacancyStatusTransitioned::class, $event);
        $vacancies = $this->createMock(VacancyRepository::class);
        $vacancies->expects(self::once())->method('find')->with($event->getEntityId())->willReturn($vacancy);
        $historyRepository = $this->createMock(VacancyStatusHistoryRepository::class);
        $historyRepository->expects(self::once())->method('findByOutboxRecordId')->with(42)->willReturn(null);
        $historyRepository->expects(self::once())->method('save')->with(self::callback(static function (VacancyStatusHistory $history) use ($vacancy): bool {
            self::assertSame($vacancy, $history->getVacancy());
            self::assertSame(VacancyStatus::Bookmarked, $history->getFromStatus());
            self::assertSame(VacancyStatus::Applying, $history->getToStatus());
            self::assertSame('First screening scheduled.', $history->getNotes());
            self::assertSame(42, $history->getOutboxRecordId());

            return true;
        }));

        new RecordVacancyStatusHistory($vacancies, $historyRepository)(
            $event->getEntityId(),
            42,
            VacancyStatus::Bookmarked->value,
            VacancyStatus::Applying->value,
            'First screening scheduled.',
            $event->getOccurredAt(),
        );
    }

    public function testDoesNotCreateADuplicateHistoryRow(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $vacancy->setStatus(VacancyStatus::Applying);
        $vacancy->recordStatusTransitioned(VacancyStatus::Bookmarked, 'start_applying', null);
        $event = $vacancy->getRecordedEvents()[0];
        self::assertInstanceOf(VacancyStatusTransitioned::class, $event);
        $vacancies = $this->createMock(VacancyRepository::class);
        $vacancies->expects(self::never())->method('find');
        $historyRepository = $this->createMock(VacancyStatusHistoryRepository::class);
        $historyRepository->expects(self::once())->method('findByOutboxRecordId')->with(42)->willReturn(new VacancyStatusHistory($vacancy, VacancyStatus::Bookmarked, VacancyStatus::Applying, null));
        $historyRepository->expects(self::never())->method('save');

        new RecordVacancyStatusHistory($vacancies, $historyRepository)(
            $event->getEntityId(),
            42,
            VacancyStatus::Bookmarked->value,
            VacancyStatus::Applying->value,
            null,
            $event->getOccurredAt(),
        );
    }
}
