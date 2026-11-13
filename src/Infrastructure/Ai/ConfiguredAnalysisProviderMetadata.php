<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisProviderMetadata;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(AnalysisProviderMetadata::class)]
final readonly class ConfiguredAnalysisProviderMetadata implements AnalysisProviderMetadata
{
    public function __construct(
        #[Autowire('%env(string:AI_PROVIDER)%')]
        private string $provider,

        #[Autowire('%env(string:AI_CANDIDATE_FIT_PROVIDER)%')]
        private string $candidateFitProvider,

        #[Autowire('%env(string:AI_LOCAL_MODEL)%')]
        private string $localModel,
    ) {
    }

    /** @return 'fake'|'local'|'gliner' */
    public function provider(AnalysisKind $kind): string
    {
        $provider = match ($kind) {
            AnalysisKind::VacancyExtraction => $this->provider,
            AnalysisKind::CandidateFit => $this->candidateFitProvider,
        };

        return match (true) {
            'fake' === $provider, 'local' === $provider => $provider,
            'gliner' === $provider && AnalysisKind::VacancyExtraction === $kind => $provider,
            default => throw new \LogicException('Unsupported AI provider configuration.'),
        };
    }

    public function model(AnalysisKind $kind): string
    {
        return match ($this->provider($kind)) {
            'fake' => 'fake',
            'local' => $this->requiredModel(),
            'gliner' => 'fastino/gliner2.5-multi-v1',
        };
    }

    private function requiredModel(): string
    {
        $model = trim($this->localModel);
        if ('' === $model) {
            throw new \LogicException('The local AI model must be configured.');
        }

        return $model;
    }
}
