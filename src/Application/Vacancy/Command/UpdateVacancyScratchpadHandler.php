<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;

final readonly class UpdateVacancyScratchpadHandler implements CommandHandler
{
    public function __construct(private VacancyRepository $vacancies)
    {
    }

    public function __invoke(UpdateVacancyScratchpad $command): Vacancy
    {
        $vacancy = $this->vacancies->findOwnedBy($command->vacancyId, $command->userId) ?? throw new \LogicException('The vacancy no longer exists.');
        $vacancy->updateScratchpadNotes($command->notes);
        $this->vacancies->save($vacancy);

        return $vacancy;
    }
}
