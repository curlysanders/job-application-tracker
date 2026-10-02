<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(VacancyRepository::class)]
final readonly class DoctrineVacancyRepository implements VacancyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(string $vacancyId, string $userId): ?Vacancy
    {
        if (!Uuid::isValid($vacancyId) || !Uuid::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(Vacancy::class)->findOneBy([
            'id' => Uuid::fromString($vacancyId),
            'user' => Uuid::fromString($userId),
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
