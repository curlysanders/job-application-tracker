<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

enum ResumeValidationStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Failed = 'failed';
}
