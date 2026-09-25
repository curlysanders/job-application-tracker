<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusWorkflow;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;

final readonly class TransitionVacancyStatusHandler implements CommandHandler
{
    public function __construct(
        private VacancyRepository $vacancies,
        private VacancyStatusWorkflow $workflow,
    ) {
    }

    public function __invoke(TransitionVacancyStatus $command): Vacancy
    {
        $vacancy = $this->vacancies->findOwnedBy($command->vacancyId, $command->userId)
            ?? throw new \LogicException('The vacancy no longer exists.');

        $this->workflow->apply($vacancy, $command->transition);

        $this->vacancies->save($vacancy);

        return $vacancy;
    }
}
