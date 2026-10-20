<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

interface AnalysisProviderMetadata
{
    public function provider(): string;

    public function model(): string;
}
