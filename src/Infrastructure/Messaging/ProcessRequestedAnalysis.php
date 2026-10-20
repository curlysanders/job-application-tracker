<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Application\Ai\ProcessAnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequested;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class ProcessRequestedAnalysis
{
    public function __construct(private ProcessAnalysisRequest $process)
    {
    }

    public function __invoke(AnalysisRequested $event): void
    {
        $fingerprint = $event->changedProperties['inputFingerprint'] ?? null;
        if (!is_string($fingerprint) || 1 !== preg_match('/^[a-f0-9]{64}$/', $fingerprint)) {
            throw new \LogicException('An analysis request event must include a SHA-256 input fingerprint.');
        }

        ($this->process)($event->getEntityId(), $fingerprint);
    }
}
