<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\UpdateVacancyNextAction;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\UpdateVacancyNextActionHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\UpdateVacancyScratchpad;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\UpdateVacancyScratchpadHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use PHPUnit\Framework\TestCase;

final class UpdateVacancyDetailsHandlerTest extends TestCase
{
    public function testUpdatesOnlyTheOwnedVacancyScratchpadAndReminder(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::exactly(2))->method('findOwnedBy')->with(12, 3)->willReturn($vacancy);
        $repository->expects(self::exactly(2))->method('save')->with($vacancy);

        $updated = new UpdateVacancyScratchpadHandler($repository)(new UpdateVacancyScratchpad(3, 12, '# Prepare'));
        new UpdateVacancyNextActionHandler($repository)(new UpdateVacancyNextAction(3, 12, 'Screening call', new \DateTimeImmutable('2026-10-01 09:30:00')));

        self::assertSame($vacancy, $updated);
        self::assertSame('# Prepare', $vacancy->getScratchpadNotes());
        self::assertSame('Screening call', $vacancy->getNextActionTitle());
    }
}
