<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Shared;

use Lingoda\DomainEventsBundle\Domain\Model\Traits\EventRecorderTrait;

trait RecordsDomainEvents
{
    use EventRecorderTrait;
}
