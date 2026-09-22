<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\UserProfile;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\UserProfile\Command\UploadResume;
use CurlySanders\JobApplicationTracker\Application\UserProfile\Command\UploadResumeHandler;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUpload;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Application\UserProfile\UploadedResume;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UploadResumeHandlerTest extends TestCase
{
    public function testRejectsAnUploadForAMissingUser(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(1)->willReturn(null);
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::never())->method('uploadResume');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->expectException(\LogicException::class);
        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(1, $this->upload()));
    }

    public function testReplacesTheResumeAndDeletesThePreviousFile(): void
    {
        $user = new User();
        $user->replaceResume('resumes/1/old.pdf', 'old.pdf', 'application/pdf', new \DateTimeImmutable());
        $upload = $this->upload();
        $uploadedResume = new UploadedResume('resumes/1/new.pdf', 'new.pdf', 'application/pdf', new \DateTimeImmutable());
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(1)->willReturn($user);
        $users->expects(self::once())->method('save')->with($user);
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('uploadResume')->with($user, $upload)->willReturn($uploadedResume);
        $uploader->expects(self::once())->method('deleteResume')->with('resumes/1/old.pdf');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(1, $upload));

        self::assertSame('resumes/1/new.pdf', $user->getResumeStoragePath());
    }

    public function testRemovesTheNewFileWhenSavingMetadataFails(): void
    {
        $user = new User();
        $upload = $this->upload();
        $uploadedResume = new UploadedResume('resumes/1/new.pdf', 'new.pdf', 'application/pdf', new \DateTimeImmutable());
        $users = $this->createMock(UserRepository::class);
        $users->method('find')->willReturn($user);
        $users->expects(self::once())->method('save')->willThrowException(new \RuntimeException('Database unavailable'));
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->method('uploadResume')->willReturn($uploadedResume);
        $uploader->expects(self::once())->method('deleteResume')->with('resumes/1/new.pdf');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->expectException(\RuntimeException::class);
        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(1, $upload));
    }

    public function testLogsWhenNewFileCleanupFailsAfterPersistenceFailure(): void
    {
        $user = new User();
        $upload = $this->upload();
        $uploadedResume = new UploadedResume('resumes/1/new.pdf', 'new.pdf', 'application/pdf', new \DateTimeImmutable());
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(1)->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willThrowException(new \RuntimeException('Database unavailable'));
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('uploadResume')->with($user, $upload)->willReturn($uploadedResume);
        $uploader->expects(self::once())->method('deleteResume')->with('resumes/1/new.pdf')->willThrowException(new \RuntimeException('Storage unavailable'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            'Could not remove the unreferenced resume after a failed update.',
            self::callback(static fn (array $context): bool => 'resumes/1/new.pdf' === $context['storage_path'] && $context['exception'] instanceof \RuntimeException),
        );

        $this->expectException(\RuntimeException::class);
        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(1, $upload));
    }

    public function testLogsWhenDeletingTheReplacedFileFails(): void
    {
        $user = new User();
        $user->replaceResume('resumes/1/old.pdf', 'old.pdf', 'application/pdf', new \DateTimeImmutable());
        $upload = $this->upload();
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(1)->willReturn($user);
        $users->expects(self::once())->method('save')->with($user);
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('uploadResume')->with($user, $upload)->willReturn(new UploadedResume('resumes/1/new.pdf', 'new.pdf', 'application/pdf', new \DateTimeImmutable()));
        $uploader->expects(self::once())->method('deleteResume')->with('resumes/1/old.pdf')->willThrowException(new \RuntimeException('Storage unavailable'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            'Could not remove the replaced resume.',
            self::callback(static fn (array $context): bool => 'resumes/1/old.pdf' === $context['storage_path'] && $context['exception'] instanceof \RuntimeException),
        );

        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(1, $upload));
    }

    private function upload(): ResumeUpload
    {
        $stream = fopen('php://temp', 'rb');
        self::assertIsResource($stream);

        return new ResumeUpload($stream, 'resume.pdf', 'application/pdf', 1);
    }
}
