<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\UserProfile;

use CurlySanders\JobApplicationTracker\Application\UserProfile\PendingResumeValidationRepository;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeFileValidator;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeValidationOutcome;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ValidatePendingResume;
use CurlySanders\JobApplicationTracker\Domain\User\PendingResume;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class ValidatePendingResumeTest extends TestCase
{
    private const string USER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';
    private const string VALIDATION_ID = '018f8e3e-5678-7abc-8def-0123456789ab';

    public function testValidatesAndPromotesTheCurrentCandidate(): void
    {
        $pending = new PendingResume(Uuid::fromString(self::VALIDATION_ID), 'resumes/user/pending/resume.pdf', 'resume.pdf', 'application/pdf', new \DateTimeImmutable());
        $repository = $this->createMock(PendingResumeValidationRepository::class);
        $repository->expects(self::once())->method('findPendingResume')->with(self::USER_ID, self::VALIDATION_ID)->willReturn($pending);
        $repository->expects(self::once())->method('completePendingResumeValidation')->with(
            self::USER_ID,
            self::VALIDATION_ID,
            self::callback(static fn (ResumeValidationOutcome $outcome): bool => $outcome->valid && null === $outcome->failure),
        )->willReturn('resumes/user/current.pdf');
        $stream = fopen('php://temp', 'rb');
        self::assertIsResource($stream);
        $resumes = $this->createMock(ResumeUploaderService::class);
        $resumes->expects(self::once())->method('readResume')->with($pending->storagePath)->willReturn($stream);
        $resumes->expects(self::once())->method('deleteResume')->with('resumes/user/current.pdf');
        $validator = $this->createMock(ResumeFileValidator::class);
        $validator->expects(self::once())->method('isValid')->with($stream, 'application/pdf')->willReturn(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        new ValidatePendingResume($repository, $resumes, $validator, $logger)(self::USER_ID, self::VALIDATION_ID);
    }

    public function testIgnoresAStaleDelivery(): void
    {
        $repository = $this->createMock(PendingResumeValidationRepository::class);
        $repository->expects(self::once())->method('findPendingResume')->with(self::USER_ID, self::VALIDATION_ID)->willReturn(null);
        $resumes = $this->createMock(ResumeUploaderService::class);
        $resumes->expects(self::never())->method('readResume');
        $validator = $this->createMock(ResumeFileValidator::class);
        $validator->expects(self::never())->method('isValid');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        new ValidatePendingResume($repository, $resumes, $validator, $logger)(self::USER_ID, self::VALIDATION_ID);
    }
}
