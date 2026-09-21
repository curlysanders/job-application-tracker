<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Storage;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUpload;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Application\UserProfile\UploadedResume;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsAlias(ResumeUploaderService::class)]
final readonly class FlysystemResumeUploaderService implements ResumeUploaderService
{
    public function __construct(
        #[Autowire(service: 'default.storage')]
        private FilesystemOperator $storage,
    ) {
    }

    public function uploadResume(User $user, ResumeUpload $upload): UploadedResume
    {
        $userId = $user->getId();
        if (null === $userId) {
            throw new \LogicException('A resume can only be uploaded for a persisted user.');
        }

        $storagePath = sprintf('resumes/%d/%s.%s', $userId, Uuid::v7()->toRfc4122(), $upload->extension());
        $this->storage->writeStream($storagePath, $upload->stream);

        return new UploadedResume(
            $storagePath,
            $upload->originalFilename,
            $upload->mimeType,
            new \DateTimeImmutable(),
        );
    }

    public function readResume(string $storagePath): mixed
    {
        return $this->storage->readStream($storagePath);
    }

    public function deleteResume(string $storagePath): void
    {
        $this->storage->delete($storagePath);
    }
}
