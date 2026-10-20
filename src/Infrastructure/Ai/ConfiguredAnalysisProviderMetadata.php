<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisProviderMetadata;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(AnalysisProviderMetadata::class)]
final readonly class ConfiguredAnalysisProviderMetadata implements AnalysisProviderMetadata
{
    public function __construct(
        #[Autowire('%env(string:AI_PROVIDER)%')]
        private string $provider,

        #[Autowire('%env(string:AI_LOCAL_MODEL)%')]
        private string $localModel,
    ) {
    }

    public function provider(): string
    {
        return match ($this->provider) {
            'fake', 'local' => $this->provider,
            default => throw new \LogicException('Unsupported AI provider configuration.'),
        };
    }

    public function model(): string
    {
        return 'fake' === $this->provider ? 'fake' : $this->requiredModel();
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
