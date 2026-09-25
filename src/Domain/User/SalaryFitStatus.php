<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

enum SalaryFitStatus: string
{
    case MeetsTarget = 'MEETS_TARGET';
    case BelowTarget = 'BELOW_TARGET';
    case NotConfigured = 'NOT_CONFIGURED';
    case ConversionUnavailable = 'CONVERSION_UNAVAILABLE';
}
