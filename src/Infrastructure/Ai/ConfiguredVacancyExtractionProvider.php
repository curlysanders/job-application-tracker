<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(VacancyExtractionProvider::class)]
final readonly class ConfiguredVacancyExtractionProvider implements VacancyExtractionProvider
{
    public function __construct(
        private FakeVacancyExtractionProvider $fake,
        private LocalOllamaVacancyExtractionProvider $local,
        #[Autowire('%env(string:AI_PROVIDER)%')]
        private string $provider,
        #[Autowire('%env(bool:AI_LOCAL_PROVIDER_ENABLED)%')]
        private bool $localEnabled,
    ) {
    }

    public function extract(VacancyExtractionInput $input): VacancyExtractionResult
    {
        return match ($this->provider) {
            'fake' => $this->fake->extract($input),
            'local' => $this->localProvider()->extract($input),
            default => throw new \LogicException(sprintf('Unsupported AI provider "%s".', $this->provider)),
        };
    }

    private function localProvider(): LocalOllamaVacancyExtractionProvider
    {
        if (!$this->localEnabled) {
            throw new \LogicException('Local AI inference is disabled until its benchmark admission gate passes.');
        }

        return $this->local;
    }
}
