<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;

final readonly class PendingResume
{
    public function __construct(
        public Identifier $validationId,
        public string $storagePath,
        public string $originalFilename,
        public string $mimeType,
        public \DateTimeImmutable $uploadedAt,
    ) {
        ResumeMetadata::assertValid($storagePath, $originalFilename, $mimeType);
    }
}
