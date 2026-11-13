<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;

interface AnalysisProviderMetadata
{
    public function provider(AnalysisKind $kind): string;

    public function model(AnalysisKind $kind): string;
}
