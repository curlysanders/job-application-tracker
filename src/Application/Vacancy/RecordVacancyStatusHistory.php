<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;

final readonly class RecordVacancyStatusHistory
{
    public function __construct(
        private VacancyRepository $vacancies,
        private VacancyStatusHistoryRepository $history,
    ) {
    }

    public function __invoke(
        string $vacancyId,
        int $outboxRecordId,
        string $fromStatus,
        string $toStatus,
        ?string $note,
        \DateTimeImmutable $transitionedAt,
    ): void {
        if (null !== $this->history->findByOutboxRecordId($outboxRecordId)) {
            return;
        }

        $vacancy = $this->vacancies->find($vacancyId);
        if (null === $vacancy) {
            return;
        }

        $this->history->save(new VacancyStatusHistory(
            $vacancy,
            VacancyStatus::from($fromStatus),
            VacancyStatus::from($toStatus),
            $note,
            $transitionedAt,
            $outboxRecordId,
        ));
    }
}
