<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

use Psr\Log\LoggerInterface;

final readonly class ValidatePendingResume
{
    public function __construct(
        private PendingResumeValidationRepository $pendingResumes,
        private ResumeUploaderService $resumes,
        private ResumeFileValidator $validator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(string $userId, string $validationId): void
    {
        $pending = $this->pendingResumes->findPendingResume($userId, $validationId);
        if (null === $pending) {
            return;
        }

        $stream = $this->resumes->readResume($pending->storagePath);
        try {
            $outcome = $this->validator->isValid($stream, $pending->mimeType)
                ? ResumeValidationOutcome::valid()
                : ResumeValidationOutcome::invalid('The uploaded file is not a valid PDF or DOCX document.');
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $cleanupPath = $this->pendingResumes->completePendingResumeValidation($userId, $validationId, $outcome);
        if (null === $cleanupPath) {
            return;
        }

        try {
            $this->resumes->deleteResume($cleanupPath);
        } catch (\Throwable $exception) {
            $this->logger->warning('Could not remove a resume after validation completed.', [
                'storage_path' => $cleanupPath,
                'validation_id' => $validationId,
                'exception' => $exception,
            ]);
        }
    }
}
