<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeValidationOutcome;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationStatus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
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
        self::assertSame($pending->storagePath, $repository->findPendingResume($user->getId()->toRfc4122(), $pending->validationId->toRfc4122())?->storagePath);
        self::assertSame('resumes/current.pdf', $repository->completePendingResumeValidation($user->getId()->toRfc4122(), $pending->validationId->toRfc4122(), ResumeValidationOutcome::valid()));
        self::assertNull($repository->completePendingResumeValidation($user->getId()->toRfc4122(), $pending->validationId->toRfc4122(), ResumeValidationOutcome::valid()));

        $entityManager->clear();
        $saved = $entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $saved);
        self::assertSame('resumes/pending.pdf', $saved->getResumeStoragePath());
        self::assertSame(ResumeValidationStatus::Validated, $saved->getResumeValidationStatus());

        $saved->stageResume('resumes/rejected.pdf', 'rejected.pdf', 'application/pdf', new \DateTimeImmutable());
        $entityManager->flush();
        $rejected = $saved->getPendingResume();
        self::assertNotNull($rejected);
        self::assertSame('resumes/rejected.pdf', $repository->completePendingResumeValidation($saved->getId()->toRfc4122(), $rejected->validationId->toRfc4122(), ResumeValidationOutcome::invalid('Invalid document')));
        self::assertSame(ResumeValidationStatus::Failed, $saved->getResumeValidationStatus());
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
        self::assertSame($vacancy->getId()->toRfc4122(), $vacancies->find($vacancy->getId()->toRfc4122())?->getId()->toRfc4122());
        self::assertSame($vacancy->getId()->toRfc4122(), $vacancies->findOwnedBy($vacancy->getId()->toRfc4122(), $user->getId()->toRfc4122())?->getId()->toRfc4122());

        $history = new DoctrineVacancyStatusHistoryRepository($entityManager);
        $older = new VacancyStatusHistory($vacancy, VacancyStatus::Bookmarked, VacancyStatus::Applying, null, new \DateTimeImmutable('-1 hour'), 101);
        $newer = new VacancyStatusHistory($vacancy, VacancyStatus::Applying, VacancyStatus::Applied, 'Applied', new \DateTimeImmutable(), 102);
        $history->save($older);
        $history->save($newer);
        self::assertSame(102, $history->findByOutboxRecordId(102)?->getOutboxRecordId());
        self::assertNull($history->findByOutboxRecordId(999));
        self::assertSame([102, 101], array_map(static fn (VacancyStatusHistory $entry): ?int => $entry->getOutboxRecordId(), $history->findForVacancy($vacancy)));

        $vacancies->remove($vacancy);
        self::assertNull($vacancies->find($vacancy->getId()->toRfc4122()));
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
