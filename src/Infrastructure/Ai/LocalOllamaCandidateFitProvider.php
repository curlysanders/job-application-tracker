<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\CandidateFitProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitReport;

final readonly class LocalOllamaCandidateFitProvider implements CandidateFitProvider
{
    public function __construct(private LocalOllamaInference $inference)
    {
    }

    public function assess(CandidateFitInput $input): CandidateFitReport
    {
        /** @var CandidateFitReport $result */
        $result = $this->inference->invoke(
            'Compare the resume against the vacancy. Return only evidence-backed strengths and gaps, with excerpts from both inputs. Do not calculate an opaque score.',
            "Vacancy:\n{$input->vacancyText}\n\nResume:\n{$input->resumeText}",
            CandidateFitReport::class,
        );

        $result->assertValid();

        return $result;
    }
}
