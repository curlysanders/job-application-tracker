<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VacancyRepository::class)]
final readonly class DoctrineVacancyRepository implements VacancyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(string $vacancyId): ?Vacancy
    {
        if (!Identifier::isValid($vacancyId)) {
            return null;
        }

        return $this->entityManager->find(Vacancy::class, Identifier::fromString($vacancyId));
    }

    public function findOwnedBy(
        string $vacancyId,
        string $userId,
    ): ?Vacancy {
        if (!Identifier::isValid($vacancyId) || !Identifier::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(Vacancy::class)->findOneBy([
            'id' => Identifier::fromString($vacancyId),
            'user' => Identifier::fromString($userId),
        ]);
    }

    public function save(Vacancy $vacancy): void
    {
        $this->entityManager->persist($vacancy);
        $this->entityManager->flush();
    }

    public function remove(Vacancy $vacancy): void
    {
        $this->entityManager->remove($vacancy);
        $this->entityManager->flush();
    }
}
