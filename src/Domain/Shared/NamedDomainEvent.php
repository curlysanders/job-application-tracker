<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

use Carbon\CarbonImmutable;
use Lingoda\DomainEventsBundle\Domain\Model\DomainEvent;
use Lingoda\DomainEventsBundle\Domain\Model\Traits\DomainEventTrait;
use Symfony\Component\Uid\Uuid;

abstract class NamedDomainEvent implements DomainEvent
{
    use DomainEventTrait;

    /** @param array<string, mixed> $changedProperties */
    final public function __construct(Uuid $aggregateId, public readonly array $changedProperties)
    {
        $this->init($aggregateId->toRfc4122());
    }

    /** @param array<string, mixed> $changedProperties */
    final public static function reconstitute(string $entityId, CarbonImmutable $occurredAt, array $changedProperties): static
    {
        $event = new static(Uuid::fromString($entityId), $changedProperties);
        $event->entityId = $entityId;
        $event->occurredAt = $occurredAt;

        return $event;
    }
}
