<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\TechStackSuggestion;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldProposal;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\FakeVacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\VacancyCorpusEvaluator;
use PHPUnit\Framework\TestCase;

final class VacancyCorpusEvaluatorTest extends TestCase
{
    public function testItPassesOnlyWhenEveryCaseIsValidAndTheFieldF1MeetsTheGate(): void
    {
        $response = new VacancyExtractionResult();
        $response->proposals = [
            new VacancyFieldProposal('title', 'PHP developer', 'PHP developer', 1.0),
            new VacancyFieldProposal('workMode', 'HYBRID', 'hybrid', 1.0),
        ];
        $response->techStacks = [new TechStackSuggestion('Symfony', 'Symfony', 1.0)];
        $provider = new FakeVacancyExtractionProvider($response);

        $result = new VacancyCorpusEvaluator()->evaluate([
            ['id' => 'one', 'text' => 'Example', 'expected' => ['title' => 'PHP developer', 'workMode' => 'HYBRID', 'techStacks' => 'Symfony']],
        ], $provider);

        self::assertSame(1, $result['valid']);
        self::assertSame(1.0, $result['f1']);
        self::assertSame(1.0, $result['criticalF1']);
        self::assertTrue($result['passed']);
        self::assertSame(1, $provider->invocations());
    }

    public function testItFailsWhenAProviderCannotReturnAValidResponse(): void
    {
        $provider = new class implements VacancyExtractionProvider {
            public function extract(VacancyExtractionInput $input): VacancyExtractionResult
            {
                throw new \RuntimeException('invalid response');
            }
        };

        $result = new VacancyCorpusEvaluator()->evaluate([
            ['id' => 'one', 'text' => 'Example', 'expected' => ['title' => 'PHP developer']],
        ], $provider);

        self::assertSame(0, $result['valid']);
        self::assertSame(0.0, $result['criticalF1']);
        self::assertFalse($result['passed']);
    }

    public function testTheCriticalScoreIgnoresUnlabelledOptionalFields(): void
    {
        $response = new VacancyExtractionResult();
        $response->proposals = [
            new VacancyFieldProposal('title', 'PHP developer', 'PHP developer', 1.0),
            new VacancyFieldProposal('requirements', 'Symfony experience', 'Symfony experience', 1.0),
        ];
        $provider = new FakeVacancyExtractionProvider($response);

        $result = new VacancyCorpusEvaluator()->evaluate([
            ['id' => 'one', 'text' => 'Example', 'expected' => ['title' => 'PHP developer']],
        ], $provider);

        self::assertLessThan(1.0, $result['f1']);
        self::assertSame(1.0, $result['criticalF1']);
        self::assertTrue($result['criticalPassed']);
    }

    public function testItReportsMedianAndNearestRankP95ForAnEvenNumberOfCases(): void
    {
        $provider = new class implements VacancyExtractionProvider {
            private int $calls = 0;

            public function extract(VacancyExtractionInput $input): VacancyExtractionResult
            {
                usleep(0 === $this->calls++ ? 10_000 : 100_000);

                $result = new VacancyExtractionResult();
                $result->proposals = [new VacancyFieldProposal('title', 'Developer', 'Developer', 1.0)];

                return $result;
            }
        };
        $cases = array_fill(0, 2, ['id' => 'case', 'text' => 'Example', 'expected' => ['title' => 'Developer']]);

        $result = new VacancyCorpusEvaluator()->evaluate($cases, $provider);

        self::assertGreaterThan(20.0, $result['medianLatencyMs']);
        self::assertLessThan($result['maxLatencyMs'], $result['medianLatencyMs']);
        self::assertSame($result['maxLatencyMs'], $result['p95LatencyMs']);
    }
}
