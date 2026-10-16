<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

interface DomainEvent
{
    public function aggregateId(): Identifier;

    public function occurredAt(): \DateTimeImmutable;
}
