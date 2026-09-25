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
use PHPUnit\Framework\TestCase;

final class TransitionVacancyStatusHandlerTest extends TestCase
{
    public function testTransitionsAnOwnedVacancyAndSavesIt(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(11, 7)->willReturn($vacancy);
        $repository->expects(self::once())->method('save')->with($vacancy);

        $result = new TransitionVacancyStatusHandler($repository, $this->workflow())(
            new TransitionVacancyStatus(7, 11, 'start_applying'),
        );

        self::assertSame($vacancy, $result);
        self::assertSame(VacancyStatus::Applying, $vacancy->getStatus());
    }

    public function testRejectsAnUnavailableTransitionWithoutSaving(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(11, 7)->willReturn($vacancy);
        $repository->expects(self::never())->method('save');

        $this->expectException(VacancyStatusTransitionNotAllowed::class);
        new TransitionVacancyStatusHandler($repository, $this->workflow())(
            new TransitionVacancyStatus(7, 11, 'mark_applied'),
        );
    }

    public function testRejectsTransitionsForAnotherUsersVacancy(): void
    {
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(11, 7)->willReturn(null);

        $this->expectException(\LogicException::class);
        new TransitionVacancyStatusHandler($repository, $this->workflow())(
            new TransitionVacancyStatus(7, 11, 'start_applying'),
        );
    }

    private function workflow(): VacancyStatusWorkflow
    {
        return new class implements VacancyStatusWorkflow {
            public function apply(Vacancy $vacancy, string $transition): void
            {
                if ('start_applying' !== $transition || VacancyStatus::Bookmarked !== $vacancy->getStatus()) {
                    throw new VacancyStatusTransitionNotAllowed($transition, new \LogicException());
                }

                $vacancy->setStatus(VacancyStatus::Applying);
            }
        };
    }
}
