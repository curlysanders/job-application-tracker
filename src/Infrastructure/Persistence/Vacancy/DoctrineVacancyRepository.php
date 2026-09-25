<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VacancyRepository::class)]
final readonly class DoctrineVacancyRepository implements VacancyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(int $vacancyId, int $userId): ?Vacancy
    {
        return $this->entityManager->getRepository(Vacancy::class)->findOneBy(['id' => $vacancyId, 'user' => $userId]);
    }

    public function save(Vacancy $vacancy): void
    {
        $this->entityManager->persist($vacancy);
        $this->entityManager->flush();
    }
}
