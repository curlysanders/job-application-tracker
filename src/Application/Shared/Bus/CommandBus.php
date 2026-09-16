<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Shared\Bus;

interface CommandBus
{
    public function dispatch(object $command): mixed;
}
