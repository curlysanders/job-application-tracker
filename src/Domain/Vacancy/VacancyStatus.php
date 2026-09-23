<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

enum VacancyStatus: string
{
    case Bookmarked = 'BOOKMARKED';
    case Applying = 'APPLYING';
    case Applied = 'APPLIED';
    case Interviewing = 'INTERVIEWING';
    case Negotiating = 'NEGOTIATING';
    case Accepted = 'ACCEPTED';
    case IWithdrew = 'I_WITHDREW';
    case NotSelected = 'NOT_SELECTED';
    case NoResponse = 'NO_RESPONSE';
}
