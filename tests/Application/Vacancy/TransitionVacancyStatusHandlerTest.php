<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\TransitionVacancyStatus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\TransitionVacancyStatusHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Exception\VacancyStatusTransitionNotAllowed;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusWorkflow;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusTransitioned;
use PHPUnit\Framework\TestCase;

final class TransitionVacancyStatusHandlerTest extends TestCase
{
    private const string USER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';
    private const string VACANCY_ID = '018f8e3e-5678-7abc-8def-0123456789ab';

    public function testTransitionsAnOwnedVacancyAndSavesIt(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(self::VACANCY_ID, self::USER_ID)->willReturn($vacancy);
        $repository->expects(self::once())->method('save')->with($vacancy);

        $result = new TransitionVacancyStatusHandler($repository, $this->workflow())(
            new TransitionVacancyStatus(self::USER_ID, self::VACANCY_ID, 'start_applying'),
        );

        self::assertSame($vacancy, $result);
        self::assertSame(VacancyStatus::Applying, $vacancy->getStatus());
        $event = $vacancy->getRecordedEvents()[0];
        self::assertInstanceOf(VacancyStatusTransitioned::class, $event);
        self::assertSame([
            'fromStatus' => VacancyStatus::Bookmarked->value,
            'toStatus' => VacancyStatus::Applying->value,
            'transition' => 'start_applying',
            'note' => null,
        ], $event->changedProperties);
    }

    public function testRejectsAnUnavailableTransitionWithoutSaving(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(self::VACANCY_ID, self::USER_ID)->willReturn($vacancy);
        $repository->expects(self::never())->method('save');

        $this->expectException(VacancyStatusTransitionNotAllowed::class);
        new TransitionVacancyStatusHandler($repository, $this->workflow())(
            new TransitionVacancyStatus(self::USER_ID, self::VACANCY_ID, 'mark_applied'),
        );
    }

    public function testRejectsTransitionsForAnotherUsersVacancy(): void
    {
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(self::VACANCY_ID, self::USER_ID)->willReturn(null);

        $this->expectException(\LogicException::class);
        new TransitionVacancyStatusHandler($repository, $this->workflow())(
            new TransitionVacancyStatus(self::USER_ID, self::VACANCY_ID, 'start_applying'),
        );
    }

    private function workflow(): VacancyStatusWorkflow
    {
        return new class implements VacancyStatusWorkflow {
            public function apply(Vacancy $vacancy, string $transition, ?string $note = null): void
            {
                if ('start_applying' !== $transition || VacancyStatus::Bookmarked !== $vacancy->getStatus()) {
                    throw new VacancyStatusTransitionNotAllowed($transition, new \LogicException());
                }

                $vacancy->setStatus(VacancyStatus::Applying);
            }
        };
    }
}
