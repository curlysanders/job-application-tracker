<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

enum WorkMode: string
{
    case Remote = 'REMOTE';
    case Hybrid = 'HYBRID';
    case Onsite = 'ONSITE';
}
