<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;

final readonly class SetVacancyArchivedHandler implements CommandHandler
{
    public function __construct(private VacancyRepository $vacancies)
    {
    }

    public function __invoke(SetVacancyArchived $command): void
    {
        $vacancy = $this->vacancies->findOwnedBy($command->vacancyId, $command->userId)
            ?? throw new \LogicException('The vacancy no longer exists.');

        if ($command->archived) {
            $vacancy->archive();
        } else {
            $vacancy->restore();
        }

        $this->vacancies->save($vacancy);
    }
}
