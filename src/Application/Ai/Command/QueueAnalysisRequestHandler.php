<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai\Command;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisProviderMetadata;
use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;

final readonly class QueueAnalysisRequestHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private AnalysisRequestRepository $requests,
        private AnalysisCipher $cipher,
        private AnalysisProviderMetadata $providerMetadata,
    ) {
    }

    public function __invoke(QueueAnalysisRequest $command): AnalysisRequest
    {
        $user = $this->users->find($command->userId);
        if (null === $user) {
            throw new \LogicException('The authenticated user no longer exists.');
        }

        $plaintext = $this->inputJson($command);
        $ciphertext = $this->cipher->encrypt($plaintext);
        $fingerprint = $this->cipher->fingerprint($plaintext);
        $expiresAt = new \DateTimeImmutable('+24 hours');
        $provider = $this->providerMetadata->provider();
        $model = $this->providerMetadata->model();

        if (null === $command->requestId) {
            $request = new AnalysisRequest($user, $command->kind, $ciphertext, $fingerprint, $provider, $model, $expiresAt);
        } else {
            $request = $this->requests->findOwnedBy($command->requestId, $command->userId)
                ?? throw new \LogicException('The analysis request no longer exists.');
            if ($request->kind !== $command->kind) {
                throw new \LogicException('An analysis request kind cannot be changed.');
            }
            $request->replaceInput($ciphertext, $fingerprint, $provider, $model, $expiresAt);
        }

        $this->requests->save($request);

        return $request;
    }

    private function inputJson(QueueAnalysisRequest $command): string
    {
        $input = match ($command->kind) {
            AnalysisKind::VacancyExtraction => new VacancyExtractionInput($command->vacancyText, $command->language),
            AnalysisKind::CandidateFit => new CandidateFitInput($command->vacancyText, $command->resumeText ?? throw new \InvalidArgumentException('Candidate fit requires resume text.')),
        };

        return json_encode($input, JSON_THROW_ON_ERROR);
    }
}
