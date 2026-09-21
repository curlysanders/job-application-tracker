<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Storage;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Application\UserProfile\UploadedResume;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

#[AsAlias(ResumeUploaderService::class)]
final readonly class FlysystemResumeUploaderService implements ResumeUploaderService
{
    /** @var array<string, string> */
    private const array EXTENSIONS = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public function __construct(
        #[Autowire(service: 'default.storage')]
        private FilesystemOperator $storage,
    ) {
    }

    public function uploadResume(User $user, UploadedFile $file): UploadedResume
    {
        $userId = $user->getId();
        if (null === $userId) {
            throw new \LogicException('A resume can only be uploaded for a persisted user.');
        }

        $mimeType = $file->getMimeType();
        if (!is_string($mimeType) || !isset(self::EXTENSIONS[$mimeType])) {
            throw new \InvalidArgumentException('Only PDF and DOCX resumes are supported.');
        }

        $stream = fopen($file->getPathname(), 'rb');
        if (false === $stream) {
            throw new \RuntimeException('The uploaded resume could not be read.');
        }

        $storagePath = sprintf('resumes/%d/%s.%s', $userId, Uuid::v7()->toRfc4122(), self::EXTENSIONS[$mimeType]);
        try {
            $this->storage->writeStream($storagePath, $stream);
        } finally {
            fclose($stream);
        }

        return new UploadedResume(
            $storagePath,
            $file->getClientOriginalName(),
            $mimeType,
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
