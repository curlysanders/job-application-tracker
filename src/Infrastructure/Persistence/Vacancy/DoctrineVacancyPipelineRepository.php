<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\PipelineStatuses;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\VacancyPipeline;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\VacancyPipelineRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\VacancyPipelineVacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(VacancyPipelineRepository::class)]
final readonly class DoctrineVacancyPipelineRepository implements VacancyPipelineRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forUser(int $userId, ?VacancyStatus $selectedStatus): VacancyPipeline
    {
        $counts = array_fill_keys(array_map(static fn (VacancyStatus $status): string => $status->value, PipelineStatuses::all()), 0);
        /** @var list<array{status: string, total: string}> $countRows */
        $countRows = $this->entityManager->createQueryBuilder()
            ->select('vacancy.status AS status, COUNT(vacancy.id) AS total')
            ->from(Vacancy::class, 'vacancy')
            ->where('IDENTITY(vacancy.user) = :userId')
            ->andWhere('vacancy.archived = false')
            ->andWhere('vacancy.status IN (:statuses)')
            ->setParameter('userId', $userId)
            ->setParameter('statuses', PipelineStatuses::all())
            ->groupBy('vacancy.status')
            ->getQuery()
            ->getScalarResult();

        foreach ($countRows as $row) {
            $status = $row['status'];
            $total = $row['total'];
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $total;
            }
        }

        $builder = $this->entityManager->createQueryBuilder()
            ->select('vacancy, company, recruiter')
            ->from(Vacancy::class, 'vacancy')
            ->leftJoin('vacancy.company', 'company')
            ->leftJoin('vacancy.recruiter', 'recruiter')
            ->where('IDENTITY(vacancy.user) = :userId')
            ->andWhere('vacancy.archived = false')
            ->andWhere('vacancy.status IN (:statuses)')
            ->setParameter('userId', $userId)
            ->setParameter('statuses', PipelineStatuses::all())
            ->orderBy('vacancy.dateAdded', 'DESC')
            ->addOrderBy('vacancy.id', 'DESC');

        if (null !== $selectedStatus) {
            $builder->andWhere('vacancy.status = :selectedStatus')->setParameter('selectedStatus', $selectedStatus);
        }

        /** @var list<Vacancy> $vacancies */
        $vacancies = $builder->getQuery()->getResult();

        return new VacancyPipeline($counts, array_map(
            static fn (Vacancy $vacancy): VacancyPipelineVacancy => new VacancyPipelineVacancy(
                $vacancy->getTitle(),
                $vacancy->getCompany()?->getName(),
                $vacancy->getRecruiter()?->getAgencyName(),
                $vacancy->getStatus(),
            ),
            $vacancies,
        ));
    }
}
