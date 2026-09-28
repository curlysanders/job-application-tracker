<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Workflow;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusHistoryRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
use CurlySanders\JobApplicationTracker\Infrastructure\Workflow\RecordVacancyStatusTransition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

final class RecordVacancyStatusTransitionTest extends TestCase
{
    public function testRecordsTheWorkflowTransitionAndOptionalNote(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyStatusHistoryRepository::class);
        $repository->expects($this->once())->method('add')->with(self::callback(static function (VacancyStatusHistory $history) use ($vacancy): bool {
            self::assertSame($vacancy, $history->getVacancy());
            self::assertSame(VacancyStatus::Bookmarked, $history->getFromStatus());
            self::assertSame(VacancyStatus::Applying, $history->getToStatus());
            self::assertSame('First screening scheduled.', $history->getNotes());

            return true;
        }));

        new RecordVacancyStatusTransition($repository)(new TransitionEvent(
            $vacancy,
            new Marking([VacancyStatus::Bookmarked->value => 1]),
            new Transition('start_applying', VacancyStatus::Bookmarked->value, VacancyStatus::Applying->value),
            null,
            ['note' => 'First screening scheduled.'],
        ));
    }
}
