<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

interface RecordsEvents
{
    /** @return list<DomainEvent> */
    public function getRecordedEvents(): array;

    public function clearRecordedEvents(): void;
}
