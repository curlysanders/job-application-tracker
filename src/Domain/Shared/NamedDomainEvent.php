<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

abstract class NamedDomainEvent implements DomainEvent
{
    private \DateTimeImmutable $occurredAt;

    /** @param array<string, mixed> $changedProperties */
    final public function __construct(
        private readonly Identifier $aggregateId,
        public readonly array $changedProperties,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }

    /** @param array<string, mixed> $changedProperties */
    final public static function reconstitute(
        string $entityId,
        \DateTimeImmutable $occurredAt,
        array $changedProperties,
    ): static {
        $event = new static(Identifier::fromString($entityId), $changedProperties);
        $event->occurredAt = $occurredAt;

        return $event;
    }

    final public function aggregateId(): Identifier
    {
        return $this->aggregateId;
    }

    final public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    final public function getEntityId(): string
    {
        return $this->aggregateId->toRfc4122();
    }

    final public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
