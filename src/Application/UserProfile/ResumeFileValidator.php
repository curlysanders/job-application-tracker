<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

interface ResumeFileValidator
{
    /** @param resource $stream */
    public function isValid(mixed $stream, string $mimeType): bool;
}
