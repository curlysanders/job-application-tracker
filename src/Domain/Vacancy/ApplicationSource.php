<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

enum ApplicationSource: string
{
    case CompanySite = 'COMPANY_SITE';
    case Recruiter = 'RECRUITER';
    case Referral = 'REFERRAL';
    case JobBoard = 'JOB_BOARD';
    case LinkedIn = 'LINKEDIN';
    case Other = 'OTHER';
}
