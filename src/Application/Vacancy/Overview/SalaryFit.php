<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Overview;

enum SalaryFit: string
{
    case MeetsTarget = 'MEETS_TARGET';
    case BelowTarget = 'BELOW_TARGET';
    case Unknown = 'UNKNOWN';

    public function label(): string
    {
        return match ($this) {
            self::MeetsTarget => 'Meets target',
            self::BelowTarget => 'Below target',
            self::Unknown => 'Unknown',
        };
    }
}
