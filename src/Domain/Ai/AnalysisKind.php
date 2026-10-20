<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

enum AnalysisKind: string
{
    case VacancyExtraction = 'vacancy_extraction';
    case CandidateFit = 'candidate_fit';
}
