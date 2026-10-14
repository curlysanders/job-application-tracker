<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitReport;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\ConfiguredCandidateFitProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\ConfiguredVacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\FakeCandidateFitProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\FakeVacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\LocalOllamaCandidateFitProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\LocalOllamaInference;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\LocalOllamaVacancyExtractionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelCatalog\FallbackModelCatalog;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\AI\Platform\Result\ObjectResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\ResultConverterInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageExtractorInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

final class LocalAiProviderTest extends TestCase
{
    public function testLocalProvidersUseStructuredResultsWithTheConfiguredModelAndContext(): void
    {
        $extraction = new VacancyExtractionResult();
        $fit = new CandidateFitReport();
        $platform = $this->platform($extraction, $fit);
        $inference = new LocalOllamaInference($platform, new LockFactory(new FlockStore(sys_get_temp_dir())), 'qwen3:0.6b');

        self::assertSame($extraction, new LocalOllamaVacancyExtractionProvider($inference)->extract(new VacancyExtractionInput('Vacancy text')));
        self::assertSame($fit, new LocalOllamaCandidateFitProvider($inference)->assess(new CandidateFitInput('Vacancy text', 'Resume text')));
        self::assertCount(2, $platform->invocations);
        self::assertSame([
            'model' => 'qwen3:0.6b',
            'options' => [
                'response_format' => VacancyExtractionResult::class,
                'options' => ['num_ctx' => 2048],
                'think' => false,
            ],
        ], $platform->invocations[0]);
    }

    public function testConfiguredProvidersUseFakesByDefaultAndFailClosedForDisabledLocalInference(): void
    {
        $platform = $this->platform(new VacancyExtractionResult());
        $inference = new LocalOllamaInference($platform, new LockFactory(new FlockStore(sys_get_temp_dir())), 'qwen3:0.6b');
        $fakeExtraction = new FakeVacancyExtractionProvider();
        $fakeFit = new FakeCandidateFitProvider();
        $localExtraction = new LocalOllamaVacancyExtractionProvider($inference);
        $localFit = new LocalOllamaCandidateFitProvider($inference);

        $extractionProvider = new ConfiguredVacancyExtractionProvider($fakeExtraction, $localExtraction, 'fake', false);
        $fitProvider = new ConfiguredCandidateFitProvider($fakeFit, $localFit, 'fake', false);
        $extractionProvider->extract(new VacancyExtractionInput('Vacancy'));
        $fitProvider->assess(new CandidateFitInput('Vacancy', 'Resume'));
        self::assertSame(1, $fakeExtraction->invocations());
        self::assertSame(1, $fakeFit->invocations());

        $disabled = new ConfiguredVacancyExtractionProvider($fakeExtraction, $localExtraction, 'local', false);
        $this->expectException(\LogicException::class);
        $disabled->extract(new VacancyExtractionInput('Vacancy'));
    }

    private function platform(object ...$responses): RecordingPlatform
    {
        return new RecordingPlatform(array_values($responses));
    }
}

final class RecordingPlatform implements PlatformInterface
{
    /** @var list<array{model: string, options: array<string, mixed>}> */
    public array $invocations = [];

    /** @param list<object> $responses */
    public function __construct(private array $responses)
    {
    }

    public function invoke(string|Model $model, array|string|object $input, array $options = []): DeferredResult
    {
        $this->invocations[] = ['model' => $model instanceof Model ? $model->getName() : $model, 'options' => $options];
        $response = array_shift($this->responses) ?? new \stdClass();

        return new DeferredResult(new readonly class($response) implements ResultConverterInterface {
            public function __construct(private object $response)
            {
            }

            public function supports(Model $model): bool
            {
                return true;
            }

            public function convert(RawResultInterface $result, array $options = []): ResultInterface
            {
                return new ObjectResult($this->response);
            }

            public function getTokenUsageExtractor(): ?TokenUsageExtractorInterface
            {
                return null;
            }
        }, new InMemoryRawResult());
    }

    public function getModelCatalog(): ModelCatalogInterface
    {
        return new FallbackModelCatalog();
    }
}
