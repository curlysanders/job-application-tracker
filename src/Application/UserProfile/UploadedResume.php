<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

final readonly class UploadedResume
{
    public function __construct(
        public string $storagePath,
        public string $originalFilename,
        public string $mimeType,
        public \DateTimeImmutable $uploadedAt,
    ) {
        if ('' === trim($storagePath) || '' === trim($originalFilename) || $originalFilename !== basename($originalFilename)) {
            throw new \InvalidArgumentException('Uploaded resume metadata must contain a path and filename.');
        }
        if (!in_array($mimeType, ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true)) {
            throw new \InvalidArgumentException('Only PDF and DOCX resumes are supported.');
        }
    }
}
