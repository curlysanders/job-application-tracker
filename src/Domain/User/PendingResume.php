<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use Symfony\Component\Uid\Uuid;

final readonly class PendingResume
{
    public function __construct(
        public Uuid $validationId,
        public string $storagePath,
        public string $originalFilename,
        public string $mimeType,
        public \DateTimeImmutable $uploadedAt,
    ) {
    }
}
