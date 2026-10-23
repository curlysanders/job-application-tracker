<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftRepository;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VacancyAuthoringDraftRepository::class)]
final readonly class DoctrineVacancyAuthoringDraftRepository implements VacancyAuthoringDraftRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(
        string $draftId,
        string $userId,
    ): ?VacancyAuthoringDraft {
        if (!Identifier::isValid($draftId) || !Identifier::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(VacancyAuthoringDraft::class)->findOneBy([
            'id' => Identifier::fromString($draftId),
            'user' => Identifier::fromString($userId),
        ]);
    }

    public function save(VacancyAuthoringDraft $draft): void
    {
        $this->entityManager->persist($draft);
        $this->entityManager->flush();
    }

    public function delete(VacancyAuthoringDraft $draft): void
    {
        $this->entityManager->remove($draft);
        $this->entityManager->flush();
    }

    public function purgeExpired(\DateTimeImmutable $now): int
    {
        $deleted = $this->entityManager->createQueryBuilder()
            ->delete(VacancyAuthoringDraft::class, 'draft')
            ->where('draft.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        if (!is_int($deleted)) {
            throw new \LogicException('Deleting expired authoring drafts must return an affected-row count.');
        }

        return $deleted;
    }
}
