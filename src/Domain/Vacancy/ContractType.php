<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

enum ContractType: string
{
    case Permanent = 'PERMANENT';
    case FixedTerm = 'FIXED_TERM';
    case Contract = 'CONTRACT';
    case Freelance = 'FREELANCE';
    case Internship = 'INTERNSHIP';
    case PartTime = 'PART_TIME';
}
