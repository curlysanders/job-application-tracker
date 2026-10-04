<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use Psr\Log\LoggerInterface;

final readonly class UploadResumeHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private ResumeUploaderService $resumeUploader,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UploadResume $command): void
    {
        $user = $this->userRepository->find($command->userId);
        if (null === $user) {
            throw new \LogicException('The authenticated user no longer exists.');
        }

        $resume = $this->resumeUploader->uploadResume($user, $command->upload);

        try {
            $previousPendingPath = $user->stageResume($resume->storagePath, $resume->originalFilename, $resume->mimeType, $resume->uploadedAt);
            $this->userRepository->save($user);
        } catch (\Throwable $exception) {
            try {
                $this->resumeUploader->deleteResume($resume->storagePath);
            } catch (\Throwable $cleanupException) {
                $this->logger->warning('Could not remove the unreferenced resume after a failed update.', [
                    'storage_path' => $resume->storagePath,
                    'exception' => $cleanupException,
                ]);
            }

            throw $exception;
        }

        if (null !== $previousPendingPath) {
            try {
                $this->resumeUploader->deleteResume($previousPendingPath);
            } catch (\Throwable $cleanupException) {
                $this->logger->warning('Could not remove the superseded pending resume.', [
                    'storage_path' => $previousPendingPath,
                    'exception' => $cleanupException,
                ]);
            }
        }
    }
}
