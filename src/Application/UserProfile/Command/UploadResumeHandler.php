<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;

final readonly class UploadResumeHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private ResumeUploaderService $resumeUploader,
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
            } catch (\Throwable) {
                // The database has not changed, so preserving the original exception takes priority.
            }

            throw $exception;
        }

        if (null !== $previousPath) {
            try {
                $this->resumeUploader->deleteResume($previousPath);
            } catch (\Throwable) {
                // The active database reference is already safe; an orphan can be cleaned up later.
            }
        }
    }
}
