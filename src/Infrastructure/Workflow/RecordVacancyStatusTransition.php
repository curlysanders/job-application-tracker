<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Workflow;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusHistoryRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[AsTransitionListener(workflow: 'vacancy_status')]
final readonly class RecordVacancyStatusTransition
{
    public function __construct(private VacancyStatusHistoryRepository $history)
    {
    }

    /** @param TransitionEvent<Vacancy> $event */
    public function __invoke(TransitionEvent $event): void
    {
        $vacancy = $event->getSubject();

        $transition = $event->getTransition();
        if (null === $transition) {
            throw new \LogicException('A vacancy status transition event must include its transition.');
        }

        $note = $event->getContext()['note'] ?? null;
        if (!is_string($note) && null !== $note) {
            throw new \LogicException('A vacancy status transition note must be a string or null.');
        }

        $this->history->add(new VacancyStatusHistory(
            $vacancy,
            VacancyStatus::from($this->singlePlace($transition->getFroms())),
            VacancyStatus::from($this->singlePlace($transition->getTos())),
            $note,
        ));
    }

    /** @param array<array-key, mixed> $places */
    private function singlePlace(array $places): string
    {
        if (1 !== count($places) || !is_string($places[0] ?? null)) {
            throw new \LogicException('A vacancy status transition must have exactly one source and target status.');
        }

        return $places[0];
    }
}
