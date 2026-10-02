<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

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
}
