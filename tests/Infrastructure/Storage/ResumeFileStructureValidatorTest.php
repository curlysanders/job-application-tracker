<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Storage;

use CurlySanders\JobApplicationTracker\Infrastructure\Storage\ResumeFileStructureValidator;
use PHPUnit\Framework\TestCase;

final class ResumeFileStructureValidatorTest extends TestCase
{
    public function testValidatesPdfHeadersAndRejectsMismatchedContent(): void
    {
        self::assertTrue(new ResumeFileStructureValidator()->isValid($this->stream("%PDF-1.4\nresume"), 'application/pdf'));
        self::assertFalse(new ResumeFileStructureValidator()->isValid($this->stream('not a PDF'), 'application/pdf'));
    }

    public function testValidatesTheRequiredDocxPackageEntries(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'resume-docx-');
        self::assertNotFalse($path);
        unlink($path);
        $archive = new \ZipArchive();
        self::assertTrue($archive->open($path, \ZipArchive::CREATE));
        $archive->addFromString('[Content_Types].xml', '<Override ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>');
        $archive->addFromString('word/document.xml', '<document/>');
        self::assertTrue($archive->close());
        $contents = file_get_contents($path);
        unlink($path);
        self::assertIsString($contents);

        self::assertTrue(new ResumeFileStructureValidator()->isValid($this->stream($contents), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
        self::assertFalse(new ResumeFileStructureValidator()->isValid($this->stream('not a zip'), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
    }

    public function testRejectsEmptyAndUnsupportedInputAndRequiresAStream(): void
    {
        $validator = new ResumeFileStructureValidator();

        self::assertFalse($validator->isValid($this->stream(''), 'application/pdf'));
        self::assertFalse($validator->isValid($this->stream('plain text'), 'text/plain'));
        $closedStream = $this->stream('resume');
        fclose($closedStream);
        $this->expectException(\InvalidArgumentException::class);
        $validator->isValid($closedStream, 'application/pdf');
    }

    /** @return resource */
    private function stream(string $contents): mixed
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }
}
