<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Health;

interface ApplicationHealthCheck
{
    public function isReady(): bool;
}
