<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Shared\Bus;

interface EventBus
{
    public function dispatch(object $event): void;
}
