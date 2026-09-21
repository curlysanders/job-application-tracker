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
    }
}
