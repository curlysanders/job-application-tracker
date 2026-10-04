<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Storage;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeFileValidator;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ResumeFileValidator::class)]
final class ResumeFileStructureValidator implements ResumeFileValidator
{
    /** @param resource $stream */
    public function isValid(mixed $stream, string $mimeType): bool
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('A resume structure check requires a readable stream.');
        }

        $contents = stream_get_contents($stream);
        if (!is_string($contents) || '' === $contents) {
            return false;
        }

        return match ($mimeType) {
            'application/pdf' => $this->isPdf($contents),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->isDocx($contents),
            default => false,
        };
    }

    private function isPdf(string $contents): bool
    {
        return str_starts_with($contents, '%PDF-')
            && 'application/pdf' === new \finfo(FILEINFO_MIME_TYPE)->buffer($contents);
    }

    private function isDocx(string $contents): bool
    {
        $path = tempnam(sys_get_temp_dir(), 'resume-validation-');
        if (false === $path) {
            throw new \RuntimeException('Could not create a temporary DOCX validation file.');
        }

        try {
            if (false === file_put_contents($path, $contents)) {
                throw new \RuntimeException('Could not write a temporary DOCX validation file.');
            }

            $archive = new \ZipArchive();
            if (true !== $archive->open($path)) {
                return false;
            }

            try {
                $contentTypes = $archive->getFromName('[Content_Types].xml');

                return false !== $contentTypes
                    && false !== $archive->locateName('word/document.xml')
                    && str_contains($contentTypes, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml');
            } finally {
                $archive->close();
            }
        } finally {
            unlink($path);
        }
    }
}
