<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

trait RecordsDomainEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    /** @return list<DomainEvent> */
    public function getRecordedEvents(): array
    {
        return $this->recordedEvents;
    }

    public function clearRecordedEvents(): void
    {
        $this->recordedEvents = [];
    }

    protected function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
