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

        $previousPath = $user->getResumeStoragePath();
        $resume = $this->resumeUploader->uploadResume($user, $command->upload);

        try {
            $user->replaceResume($resume->storagePath, $resume->originalFilename, $resume->mimeType, $resume->uploadedAt);
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

        if (null !== $previousPath) {
            try {
                $this->resumeUploader->deleteResume($previousPath);
            } catch (\Throwable $cleanupException) {
                $this->logger->warning('Could not remove the replaced resume.', [
                    'storage_path' => $previousPath,
                    'exception' => $cleanupException,
                ]);
            }
        }
    }
}
