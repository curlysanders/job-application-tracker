<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai\Command;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;

final readonly class QueueAnalysisRequest
{
    public function __construct(
        public string $userId,
        public AnalysisKind $kind,

        #[\SensitiveParameter]
        public string $vacancyText,

        #[\SensitiveParameter]
        public ?string $resumeText = null,
        public string $language = 'auto',
        public ?string $requestId = null,
    ) {
    }
}
