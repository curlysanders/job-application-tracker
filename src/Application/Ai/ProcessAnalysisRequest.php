<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use Psr\Log\LoggerInterface;

final readonly class ProcessAnalysisRequest
{
    public function __construct(
        private AnalysisRequestRepository $requests,
        private AnalysisCipher $cipher,
        private VacancyExtractionProvider $vacancyExtraction,
        private CandidateFitProvider $candidateFit,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        string $requestId,
        string $inputFingerprint,
    ): void {
        $request = $this->requests->find($requestId);
        if (null === $request || !$request->isCurrentPending($inputFingerprint)) {
            return;
        }

        try {
            $payload = $this->decodePayload($this->cipher->decrypt($request->inputCiphertext));
            $result = match ($request->kind) {
                AnalysisKind::VacancyExtraction => $this->vacancyExtraction->extract($this->vacancyExtractionInput($payload)),
                AnalysisKind::CandidateFit => $this->candidateFit->assess($this->candidateFitInput($payload)),
            };
            $plaintext = json_encode($result, JSON_THROW_ON_ERROR);
            $this->requests->complete($requestId, $inputFingerprint, $this->cipher->encrypt($plaintext), $this->cipher->fingerprint($plaintext));
        } catch (\Throwable) {
            $this->requests->fail($requestId, $inputFingerprint);
            $this->logger->warning('AI analysis provider failed.', [
                'analysis_request_id' => $requestId,
                'input_fingerprint' => $inputFingerprint,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function decodePayload(string $plaintext): array
    {
        $payload = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('The analysis input payload must be a JSON object.');
        }

        $validated = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('The analysis input payload must use string keys.');
            }
            $validated[$key] = $value;
        }

        return $validated;
    }

    /** @param array<string, mixed> $payload */
    private function vacancyExtractionInput(array $payload): VacancyExtractionInput
    {
        return new VacancyExtractionInput(
            $this->requiredString($payload, 'vacancyText'),
            $this->optionalString($payload, 'language') ?? 'auto',
        );
    }

    /** @param array<string, mixed> $payload */
    private function candidateFitInput(array $payload): CandidateFitInput
    {
        return new CandidateFitInput(
            $this->requiredString($payload, 'vacancyText'),
            $this->requiredString($payload, 'resumeText'),
        );
    }

    /** @param array<string, mixed> $payload */
    private function requiredString(
        array $payload,
        string $key,
    ): string {
        $value = $payload[$key] ?? null;
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('The analysis input requires a string %s.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private function optionalString(
        array $payload,
        string $key,
    ): ?string {
        $value = $payload[$key] ?? null;
        if (null !== $value && !is_string($value)) {
            throw new \InvalidArgumentException(sprintf('The analysis input %s must be a string.', $key));
        }

        return $value;
    }
}
