<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use Carbon\CarbonImmutable;
use CurlySanders\JobApplicationTracker\Domain\Shared\DomainEvent;
use Lingoda\DomainEventsBundle\Domain\Model\DomainEvent as LingodaDomainEventContract;

final readonly class LingodaDomainEvent implements LingodaDomainEventContract
{
    public function __construct(private DomainEvent $event)
    {
    }

    public function event(): DomainEvent
    {
        return $this->event;
    }

    public function getEntityId(): string
    {
        return $this->event->aggregateId()->toRfc4122();
    }

    public function getOccurredAt(): CarbonImmutable
    {
        return CarbonImmutable::instance($this->event->occurredAt());
    }
}
