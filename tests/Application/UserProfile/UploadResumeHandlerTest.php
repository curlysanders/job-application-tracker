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
    private const string USER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';
    private const string OLD_RESUME_PATH = 'resumes/018f8e3e-1234-7abc-8def-0123456789ab/old.pdf';
    private const string NEW_RESUME_PATH = 'resumes/018f8e3e-1234-7abc-8def-0123456789ab/new.pdf';

    public function testRejectsAnUploadForAMissingUser(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(self::USER_ID)->willReturn(null);
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::never())->method('uploadResume');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->expectException(\LogicException::class);
        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(self::USER_ID, $this->upload()));
    }

    public function testReplacesTheResumeAndDeletesThePreviousFile(): void
    {
        $user = new User();
        $user->replaceResume(self::OLD_RESUME_PATH, 'old.pdf', 'application/pdf', new \DateTimeImmutable());
        $upload = $this->upload();
        $uploadedResume = new UploadedResume(self::NEW_RESUME_PATH, 'new.pdf', 'application/pdf', new \DateTimeImmutable());
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(self::USER_ID)->willReturn($user);
        $users->expects(self::once())->method('save')->with($user);
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('uploadResume')->with($user, $upload)->willReturn($uploadedResume);
        $uploader->expects(self::once())->method('deleteResume')->with(self::OLD_RESUME_PATH);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(self::USER_ID, $upload));

        self::assertSame(self::NEW_RESUME_PATH, $user->getResumeStoragePath());
    }

    public function testRemovesTheNewFileWhenSavingMetadataFails(): void
    {
        $user = new User();
        $upload = $this->upload();
        $uploadedResume = new UploadedResume(self::NEW_RESUME_PATH, 'new.pdf', 'application/pdf', new \DateTimeImmutable());
        $users = $this->createMock(UserRepository::class);
        $users->method('find')->willReturn($user);
        $users->expects(self::once())->method('save')->willThrowException(new \RuntimeException('Database unavailable'));
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->method('uploadResume')->willReturn($uploadedResume);
        $uploader->expects(self::once())->method('deleteResume')->with(self::NEW_RESUME_PATH);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $this->expectException(\RuntimeException::class);
        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(self::USER_ID, $upload));
    }

    public function testLogsWhenNewFileCleanupFailsAfterPersistenceFailure(): void
    {
        $user = new User();
        $upload = $this->upload();
        $uploadedResume = new UploadedResume(self::NEW_RESUME_PATH, 'new.pdf', 'application/pdf', new \DateTimeImmutable());
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(self::USER_ID)->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willThrowException(new \RuntimeException('Database unavailable'));
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('uploadResume')->with($user, $upload)->willReturn($uploadedResume);
        $uploader->expects(self::once())->method('deleteResume')->with(self::NEW_RESUME_PATH)->willThrowException(new \RuntimeException('Storage unavailable'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            'Could not remove the unreferenced resume after a failed update.',
            self::callback(static fn (array $context): bool => self::NEW_RESUME_PATH === $context['storage_path'] && $context['exception'] instanceof \RuntimeException),
        );

        $this->expectException(\RuntimeException::class);
        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(self::USER_ID, $upload));
    }

    public function testLogsWhenDeletingTheReplacedFileFails(): void
    {
        $user = new User();
        $user->replaceResume(self::OLD_RESUME_PATH, 'old.pdf', 'application/pdf', new \DateTimeImmutable());
        $upload = $this->upload();
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(self::USER_ID)->willReturn($user);
        $users->expects(self::once())->method('save')->with($user);
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('uploadResume')->with($user, $upload)->willReturn(new UploadedResume(self::NEW_RESUME_PATH, 'new.pdf', 'application/pdf', new \DateTimeImmutable()));
        $uploader->expects(self::once())->method('deleteResume')->with(self::OLD_RESUME_PATH)->willThrowException(new \RuntimeException('Storage unavailable'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(
            'Could not remove the replaced resume.',
            self::callback(static fn (array $context): bool => self::OLD_RESUME_PATH === $context['storage_path'] && $context['exception'] instanceof \RuntimeException),
        );

        new UploadResumeHandler($users, $uploader, $logger)(new UploadResume(self::USER_ID, $upload));
    }

    private function upload(): ResumeUpload
    {
        $stream = fopen('php://temp', 'rb');
        self::assertIsResource($stream);

        return new ResumeUpload($stream, 'resume.pdf', 'application/pdf', 1);
    }
}
