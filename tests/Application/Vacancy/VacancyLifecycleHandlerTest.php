<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\DeleteVacancy;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\DeleteVacancyHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\SetVacancyArchived;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\SetVacancyArchivedHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use PHPUnit\Framework\TestCase;

final class VacancyLifecycleHandlerTest extends TestCase
{
    public function testArchivesAndRestoresAnOwnedVacancy(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::exactly(2))->method('findOwnedBy')->with(12, 3)->willReturn($vacancy);
        $repository->expects(self::exactly(2))->method('save')->with($vacancy);
        $handler = new SetVacancyArchivedHandler($repository);

        $handler(new SetVacancyArchived(3, 12, true));
        self::assertTrue($vacancy->isArchived());
        $handler(new SetVacancyArchived(3, 12, false));
        self::assertFalse($vacancy->isArchived());
    }

    public function testDeletesOnlyAnOwnedVacancy(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(12, 3)->willReturn($vacancy);
        $repository->expects(self::once())->method('remove')->with($vacancy);

        new DeleteVacancyHandler($repository)(new DeleteVacancy(3, 12));
    }
}
