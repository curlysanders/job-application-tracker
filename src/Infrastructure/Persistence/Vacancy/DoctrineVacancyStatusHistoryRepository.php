<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusHistoryRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VacancyStatusHistoryRepository::class)]
final readonly class DoctrineVacancyStatusHistoryRepository implements VacancyStatusHistoryRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function add(VacancyStatusHistory $history): void
    {
        $this->entityManager->persist($history);
    }

    public function findForVacancy(Vacancy $vacancy): array
    {
        /** @var list<VacancyStatusHistory> $history */
        $history = $this->entityManager->createQueryBuilder()
            ->select('history')
            ->from(VacancyStatusHistory::class, 'history')
            ->where('IDENTITY(history.vacancy) = :vacancyId')
            ->setParameter('vacancyId', $vacancy->getId(), UuidType::NAME)
            ->orderBy('history.transitionedAt', 'DESC')
            ->addOrderBy('history.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $history;
    }
}
