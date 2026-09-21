<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

final readonly class ResumeUpload
{
    public const int MAXIMUM_SIZE_IN_BYTES = 5_000_000;

    /** @var array<string, string> */
    private const array EXTENSIONS_BY_MIME_TYPE = [
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    /** @param resource $stream */
    public function __construct(
        public mixed $stream,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeInBytes,
    ) {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('A resume upload must provide a readable stream.');
        }

        if (!isset(self::EXTENSIONS_BY_MIME_TYPE[$mimeType])) {
            throw new \InvalidArgumentException('Only PDF and DOCX resumes are supported.');
        }

        if ($sizeInBytes < 1 || $sizeInBytes > self::MAXIMUM_SIZE_IN_BYTES) {
            throw new \InvalidArgumentException('A resume must be between 1 byte and 5 MB.');
        }
    }

    public function extension(): string
    {
        return self::EXTENSIONS_BY_MIME_TYPE[$this->mimeType];
    }
}
