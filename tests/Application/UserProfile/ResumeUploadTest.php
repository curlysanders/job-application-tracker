<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\UserProfile;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUpload;
use PHPUnit\Framework\TestCase;

final class ResumeUploadTest extends TestCase
{
    public function testSupportsPdfAndDocxUploads(): void
    {
        $stream = fopen('php://temp', 'rb');
        self::assertIsResource($stream);

        self::assertSame('pdf', new ResumeUpload($stream, 'resume.pdf', 'application/pdf', 1)->extension());
        self::assertSame('docx', new ResumeUpload($stream, 'resume.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 1)->extension());

        fclose($stream);
    }

    public function testRejectsAnUnreadableStream(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        /* @phpstan-ignore argument.type (This test verifies the runtime stream guard.) */
        new ResumeUpload($this->invalidStream(), 'resume.pdf', 'application/pdf', 1);
    }

    public function testRejectsUnsupportedMimeTypes(): void
    {
        $stream = fopen('php://temp', 'rb');
        self::assertIsResource($stream);

        $this->expectException(\InvalidArgumentException::class);

        new ResumeUpload($stream, 'resume.txt', 'text/plain', 1);
    }

    public function testRejectsSizesOutsideTheAcceptedRange(): void
    {
        $stream = fopen('php://temp', 'rb');
        self::assertIsResource($stream);

        try {
            new ResumeUpload($stream, 'resume.pdf', 'application/pdf', 0);
            self::fail('A zero-byte resume must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertStringContainsString('between 1 byte and 5 MB', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        new ResumeUpload($stream, 'resume.pdf', 'application/pdf', ResumeUpload::MAXIMUM_SIZE_IN_BYTES + 1);
    }

    private function invalidStream(): mixed
    {
        return 'not-a-stream';
    }
}
