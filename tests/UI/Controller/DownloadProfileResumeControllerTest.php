<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\UI\Controller\DownloadProfileResumeController;
use League\Flysystem\UnableToReadFile;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DownloadProfileResumeControllerTest extends TestCase
{
    public function testReturnsNotFoundWhenThereIsNoActiveResume(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::once())->method('getUser')->willReturn(new User());
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::never())->method('readResume');

        $this->expectException(NotFoundHttpException::class);
        new DownloadProfileResumeController($security, $uploader)();
    }

    public function testTranslatesStorageReadFailuresToNotFound(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::once())->method('getUser')->willReturn($this->userWithResume());
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('readResume')->willThrowException(UnableToReadFile::fromLocation('resumes/1/resume.pdf'));

        $this->expectException(NotFoundHttpException::class);
        new DownloadProfileResumeController($security, $uploader)();
    }

    public function testRejectsAnInvalidStorageStream(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::once())->method('getUser')->willReturn($this->userWithResume());
        $uploader = $this->createMock(ResumeUploaderService::class);
        $uploader->expects(self::once())->method('readResume')->willReturn('not-a-stream');

        $this->expectException(\LogicException::class);
        new DownloadProfileResumeController($security, $uploader)();
    }

    private function userWithResume(): User
    {
        $user = new User();
        $user->replaceResume('resumes/1/resume.pdf', 'resume.pdf', 'application/pdf', new \DateTimeImmutable());

        return $user;
    }
}
