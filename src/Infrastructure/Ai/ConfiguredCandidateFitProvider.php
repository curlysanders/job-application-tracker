<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\CandidateFitProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitReport;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(CandidateFitProvider::class)]
final readonly class ConfiguredCandidateFitProvider implements CandidateFitProvider
{
    public function __construct(
        private FakeCandidateFitProvider $fake,
        private LocalOllamaCandidateFitProvider $local,

        #[Autowire('%env(string:AI_PROVIDER)%')]
        private string $provider,

        #[Autowire('%env(bool:AI_LOCAL_PROVIDER_ENABLED)%')]
        private bool $localEnabled,
    ) {
    }

    public function assess(CandidateFitInput $input): CandidateFitReport
    {
        return match ($this->provider) {
            'fake' => $this->fake->assess($input),
            'local' => $this->localProvider()->assess($input),
            default => throw new \LogicException(sprintf('Unsupported AI provider "%s".', $this->provider)),
        };
    }

    private function localProvider(): LocalOllamaCandidateFitProvider
    {
        if (!$this->localEnabled) {
            throw new \LogicException('Local AI inference is disabled until its benchmark admission gate passes.');
        }

        return $this->local;
    }
}
