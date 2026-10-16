<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application;

use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRates;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeValidationOutcome;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewFilter;
use PHPUnit\Framework\TestCase;

final class ValidationInvariantTest extends TestCase
{
    public function testExchangeRatesRejectMalformedExternalData(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExchangeRates(['USD' => '-1'], new \DateTimeImmutable());
    }

    public function testInvalidResumeOutcomeRequiresFailureReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ResumeValidationOutcome::invalid(' ');
    }

    public function testOverviewFilterRequiresPositivePage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VacancyOverviewFilter(null, null, null, null, null, false, 0);
    }
}
