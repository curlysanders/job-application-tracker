<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeValidationOutcome;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationStatus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\DoctrineAnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\DoctrinePendingResumeValidationRepository;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy\DoctrineVacancyRepository;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy\DoctrineVacancyStatusHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineAsyncRepositoriesTest extends KernelTestCase
{
    public function testPendingResumeRepositoryPromotesRejectsAndIgnoresStaleDeliveries(): void
    {
        self::bootKernel();
        $entityManager = $this->entityManager();
        $user = new User()->setEmail('pending-resume@example.com')->setPassword('hashed-password');
        $user->replaceResume('resumes/current.pdf', 'current.pdf', 'application/pdf', new \DateTimeImmutable());
        $user->stageResume('resumes/pending.pdf', 'pending.pdf', 'application/pdf', new \DateTimeImmutable());
        $entityManager->persist($user);
        $entityManager->flush();
        $pending = $user->getPendingResume();
        self::assertNotNull($pending);
        $repository = new DoctrinePendingResumeValidationRepository($entityManager);

        self::assertNull($repository->findPendingResume('not-a-uuid', $pending->validationId->toRfc4122()));
        self::assertSame($pending->storagePath, $repository->findPendingResume($user->id->toRfc4122(), $pending->validationId->toRfc4122())?->storagePath);
        self::assertSame('resumes/current.pdf', $repository->completePendingResumeValidation($user->id->toRfc4122(), $pending->validationId->toRfc4122(), ResumeValidationOutcome::valid()));
        self::assertNull($repository->completePendingResumeValidation($user->id->toRfc4122(), $pending->validationId->toRfc4122(), ResumeValidationOutcome::valid()));

        $entityManager->clear();
        $saved = $entityManager->find(User::class, $user->id);
        self::assertInstanceOf(User::class, $saved);
        self::assertSame('resumes/pending.pdf', $saved->resumeStoragePath);
        self::assertSame(ResumeValidationStatus::Validated, $saved->resumeValidationStatus);

        $saved->stageResume('resumes/rejected.pdf', 'rejected.pdf', 'application/pdf', new \DateTimeImmutable());
        $entityManager->flush();
        $rejected = $saved->getPendingResume();
        self::assertNotNull($rejected);
        self::assertSame('resumes/rejected.pdf', $repository->completePendingResumeValidation($saved->id->toRfc4122(), $rejected->validationId->toRfc4122(), ResumeValidationOutcome::invalid('Invalid document')));
        self::assertSame(ResumeValidationStatus::Failed, $saved->resumeValidationStatus);
    }

    public function testVacancyRepositoriesPersistFindAndOrderStatusHistory(): void
    {
        self::bootKernel();
        $entityManager = $this->entityManager();
        $user = new User()->setEmail('vacancy-repository@example.com')->setPassword('hashed-password');
        $entityManager->persist($user);
        $entityManager->flush();
        $vacancy = new Vacancy($user, 'Backend engineer');
        $vacancies = new DoctrineVacancyRepository($entityManager);
        $vacancies->save($vacancy);

        self::assertNull($vacancies->find('not-a-uuid'));
        self::assertSame($vacancy->id->toRfc4122(), $vacancies->find($vacancy->id->toRfc4122())?->id->toRfc4122());
        self::assertSame($vacancy->id->toRfc4122(), $vacancies->findOwnedBy($vacancy->id->toRfc4122(), $user->id->toRfc4122())?->id->toRfc4122());

        $history = new DoctrineVacancyStatusHistoryRepository($entityManager);
        $older = new VacancyStatusHistory($vacancy, VacancyStatus::Bookmarked, VacancyStatus::Applying, null, new \DateTimeImmutable('-1 hour'), 101);
        $newer = new VacancyStatusHistory($vacancy, VacancyStatus::Applying, VacancyStatus::Applied, 'Applied', new \DateTimeImmutable(), 102);
        $history->save($older);
        $history->save($newer);
        self::assertSame(102, $history->findByOutboxRecordId(102)?->outboxRecordId);
        self::assertNull($history->findByOutboxRecordId(999));
        self::assertSame([102, 101], array_map(static fn (VacancyStatusHistory $entry): ?int => $entry->outboxRecordId, $history->findForVacancy($vacancy)));

        $vacancies->remove($vacancy);
        self::assertNull($vacancies->find($vacancy->id->toRfc4122()));
    }

    public function testAnalysisRequestsAreOwnerScopedIdempotentAndPurgedAtExpiry(): void
    {
        self::bootKernel();
        $entityManager = $this->entityManager();
        $owner = new User()->setEmail('analysis-owner@example.com')->setPassword('hashed-password');
        $other = new User()->setEmail('analysis-other@example.com')->setPassword('hashed-password');
        $request = new AnalysisRequest($owner, AnalysisKind::VacancyExtraction, 'encrypted-input', str_repeat('a', 64), 'fake', 'fake', new \DateTimeImmutable('+24 hours'));
        $expired = new AnalysisRequest($owner, AnalysisKind::CandidateFit, 'expired-input', str_repeat('b', 64), 'fake', 'fake', new \DateTimeImmutable('-1 second'));
        $entityManager->persist($owner);
        $entityManager->persist($other);
        $entityManager->persist($request);
        $entityManager->persist($expired);
        $entityManager->flush();
        $repository = new DoctrineAnalysisRequestRepository($entityManager);

        self::assertSame($request->id->toRfc4122(), $repository->findOwnedBy($request->id->toRfc4122(), $owner->id->toRfc4122())?->id->toRfc4122());
        self::assertNull($repository->findOwnedBy($request->id->toRfc4122(), $other->id->toRfc4122()));
        self::assertTrue($repository->complete($request->id->toRfc4122(), str_repeat('a', 64), 'encrypted-result', str_repeat('c', 64)));
        self::assertFalse($repository->complete($request->id->toRfc4122(), str_repeat('a', 64), 'duplicate-result', str_repeat('d', 64)));

        $entityManager->clear();
        $completed = $repository->find($request->id->toRfc4122());
        self::assertInstanceOf(AnalysisRequest::class, $completed);
        self::assertSame(AnalysisState::Completed, $completed->state);
        self::assertSame('encrypted-result', $completed->result?->ciphertext);
        self::assertSame(1, $repository->purgeExpired(new \DateTimeImmutable()));
        self::assertNull($repository->find($expired->id->toRfc4122()));
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
