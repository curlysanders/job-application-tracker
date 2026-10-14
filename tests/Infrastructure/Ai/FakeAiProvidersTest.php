<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\FakeCandidateFitProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\FakeVacancyExtractionProvider;
use PHPUnit\Framework\TestCase;

final class FakeAiProvidersTest extends TestCase
{
    public function testFakesAreDeterministicAndDoNotNeedAnExternalProvider(): void
    {
        $extraction = new FakeVacancyExtractionProvider();
        $fit = new FakeCandidateFitProvider();

        self::assertSame([], $extraction->extract(new VacancyExtractionInput('Vacancy'))->proposals);
        self::assertSame([], $fit->assess(new CandidateFitInput('Vacancy', 'Resume'))->strengths);
        self::assertSame(1, $extraction->invocations());
        self::assertSame(1, $fit->invocations());
    }
}
