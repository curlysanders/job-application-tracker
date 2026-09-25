<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Workflow;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Exception\VacancyStatusTransitionNotAllowed;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusWorkflow;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsAlias(VacancyStatusWorkflow::class)]
final readonly class SymfonyVacancyStatusWorkflow implements VacancyStatusWorkflow
{
    public function __construct(
        #[Autowire(service: 'state_machine.vacancy_status')]
        private WorkflowInterface $workflow,
    ) {
    }

    public function apply(Vacancy $vacancy, string $transition): void
    {
        try {
            $this->workflow->apply($vacancy, $transition);
        } catch (NotEnabledTransitionException $exception) {
            throw new VacancyStatusTransitionNotAllowed($transition, $exception);
        }
    }
}
