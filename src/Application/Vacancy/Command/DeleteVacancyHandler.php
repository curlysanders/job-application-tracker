<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;

final readonly class DeleteVacancyHandler implements CommandHandler
{
    public function __construct(private VacancyRepository $vacancies)
    {
    }

    public function __invoke(DeleteVacancy $command): void
    {
        $vacancy = $this->vacancies->findOwnedBy($command->vacancyId, $command->userId)
            ?? throw new \LogicException('The vacancy no longer exists.');

        $vacancy->recordDeleted();
        $this->vacancies->remove($vacancy);
    }
}
