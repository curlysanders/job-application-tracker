<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

enum AnalysisState: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
