<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

final readonly class ResumeValidationOutcome
{
    private function __construct(public bool $valid, public ?string $failure)
    {
    }

    public static function valid(): self
    {
        return new self(true, null);
    }

    public static function invalid(string $failure): self
    {
        return new self(false, $failure);
    }
}
