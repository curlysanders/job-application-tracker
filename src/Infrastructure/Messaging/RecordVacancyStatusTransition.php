<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Application\Vacancy\RecordVacancyStatusHistory;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusTransitioned;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class RecordVacancyStatusTransition
{
    public function __construct(
        private RecordVacancyStatusHistory $recordHistory,
        private OutboxRecordIdContext $outboxRecord,
    ) {
    }

    public function __invoke(VacancyStatusTransitioned $event): void
    {
        $outboxRecordId = $this->outboxRecord->id()
            ?? throw new \LogicException('An asynchronous vacancy status transition must have an outbox record ID.');
        $fromStatus = $event->changedProperties['fromStatus'] ?? null;
        $toStatus = $event->changedProperties['toStatus'] ?? null;
        $note = $event->changedProperties['note'] ?? null;
        if (!is_string($fromStatus) || !is_string($toStatus) || (!is_string($note) && null !== $note)) {
            throw new \LogicException('A vacancy status transition event must contain valid status history values.');
        }

        ($this->recordHistory)(
            $event->getEntityId(),
            $outboxRecordId,
            $fromStatus,
            $toStatus,
            $note,
            $event->getOccurredAt(),
        );
    }
}
