<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

final class ResumeMetadata
{
    /** @var array<string, true> */
    private const array MIME_TYPES = [
        'application/pdf' => true,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
    ];

    public static function assertValid(string $storagePath, string $originalFilename, string $mimeType): void
    {
        if ('' === trim($storagePath) || '' === trim($originalFilename) || $originalFilename !== basename($originalFilename)) {
            throw new \InvalidArgumentException('Resume storage metadata must contain a path and a filename without directory components.');
        }
        if (!isset(self::MIME_TYPES[$mimeType])) {
            throw new \InvalidArgumentException('Only PDF and DOCX resumes are supported.');
        }
    }
}
