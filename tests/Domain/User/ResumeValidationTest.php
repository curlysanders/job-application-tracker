<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\User;

use CurlySanders\JobApplicationTracker\Domain\User\ResumeReplaced;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationRequested;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationStatus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class ResumeValidationTest extends TestCase
{
    public function testStagesThenPromotesAResumeWithoutPublishingMetadata(): void
    {
        $user = new User();
        $user->replaceResume('resumes/user/current.pdf', 'current.pdf', 'application/pdf', new \DateTimeImmutable('2026-10-02 10:00:00'));
        $user->stageResume('resumes/user/pending/new.docx', 'new.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', new \DateTimeImmutable('2026-10-02 11:00:00'));

        $pending = $user->getPendingResume();
        self::assertNotNull($pending);
        self::assertSame('resumes/user/current.pdf', $user->getResumeStoragePath());
        self::assertSame(ResumeValidationStatus::Pending, $user->getResumeValidationStatus());
        $event = $user->getRecordedEvents()[1];
        self::assertInstanceOf(ResumeValidationRequested::class, $event);
        self::assertSame(['validationId' => $pending->validationId->toRfc4122()], $event->changedProperties);

        self::assertSame('resumes/user/current.pdf', $user->promotePendingResume($pending->validationId));
        self::assertSame('resumes/user/pending/new.docx', $user->getResumeStoragePath());
        self::assertNull($user->getPendingResume());
        self::assertSame(ResumeValidationStatus::Validated, $user->getResumeValidationStatus());
        self::assertInstanceOf(ResumeReplaced::class, $user->getRecordedEvents()[2]);
    }

    public function testRejectingAStagedResumeKeepsTheCurrentResume(): void
    {
        $user = new User();
        $user->replaceResume('resumes/user/current.pdf', 'current.pdf', 'application/pdf', new \DateTimeImmutable());
        $user->stageResume('resumes/user/pending/bad.pdf', 'bad.pdf', 'application/pdf', new \DateTimeImmutable());
        $pending = $user->getPendingResume();
        self::assertNotNull($pending);

        self::assertSame('resumes/user/pending/bad.pdf', $user->rejectPendingResume($pending->validationId, 'The uploaded file is not a valid PDF or DOCX document.'));
        self::assertSame('resumes/user/current.pdf', $user->getResumeStoragePath());
        self::assertNull($user->getPendingResume());
        self::assertSame(ResumeValidationStatus::Failed, $user->getResumeValidationStatus());
        self::assertSame('The uploaded file is not a valid PDF or DOCX document.', $user->getResumeValidationFailure());
    }
}
