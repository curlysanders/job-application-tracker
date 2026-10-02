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
    private const string USER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';
    private const string VACANCY_ID = '018f8e3e-5678-7abc-8def-0123456789ab';

    public function testUpdatesOnlyTheOwnedVacancyScratchpadAndReminder(): void
    {
        $vacancy = new Vacancy(new User(), 'Senior PHP Developer');
        $repository = $this->createMock(VacancyRepository::class);
        $repository->expects(self::exactly(2))->method('findOwnedBy')->with(self::VACANCY_ID, self::USER_ID)->willReturn($vacancy);
        $repository->expects(self::exactly(2))->method('save')->with($vacancy);

        $updated = new UpdateVacancyScratchpadHandler($repository)(new UpdateVacancyScratchpad(self::USER_ID, self::VACANCY_ID, '# Prepare'));
        new UpdateVacancyNextActionHandler($repository)(new UpdateVacancyNextAction(self::USER_ID, self::VACANCY_ID, 'Screening call', new \DateTimeImmutable('2026-10-01 09:30:00')));

        self::assertSame($vacancy, $updated);
        self::assertSame('# Prepare', $vacancy->getScratchpadNotes());
        self::assertSame('Screening call', $vacancy->getNextActionTitle());
    }
}
