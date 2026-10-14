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
        self::assertFalse($result['passed']);
    }
}
