<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\SalaryFit;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverview;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewFilter;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewVacancy;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\SalaryRange;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsAlias(VacancyOverviewRepository::class)]
final readonly class DoctrineVacancyOverviewRepository implements VacancyOverviewRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.vacancy_status')]
        private WorkflowInterface $workflow,
    ) {
    }

    public function forUser(int $userId, PreferredSalary $preferredSalary, VacancyOverviewFilter $filter): VacancyOverview
    {
        $countBuilder = $this->filteredQuery($userId, $preferredSalary, $filter);
        $total = (int) $countBuilder
            ->select('COUNT(DISTINCT vacancy.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $page = min($filter->page, max(1, (int) ceil($total / VacancyOverviewFilter::PAGE_SIZE)));
        /** @var list<array{id: int|string}> $rows */
        $rows = $this->filteredQuery($userId, $preferredSalary, $filter)
            ->select('DISTINCT vacancy.id AS id')
            ->orderBy('vacancy.dateAdded', 'DESC')
            ->addOrderBy('vacancy.id', 'DESC')
            ->setFirstResult(($page - 1) * VacancyOverviewFilter::PAGE_SIZE)
            ->setMaxResults(VacancyOverviewFilter::PAGE_SIZE)
            ->getQuery()
            ->getScalarResult();
        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);

        if ([] === $ids) {
            return new VacancyOverview([], $total, $page, VacancyOverviewFilter::PAGE_SIZE);
        }

        /** @var list<Vacancy> $vacancies */
        $vacancies = $this->entityManager->createQueryBuilder()
            ->select('vacancy, company, recruiter, techStack')
            ->from(Vacancy::class, 'vacancy')
            ->leftJoin('vacancy.company', 'company')
            ->leftJoin('vacancy.recruiter', 'recruiter')
            ->leftJoin('vacancy.techStacks', 'techStack')
            ->where('vacancy.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
        $byId = [];
        foreach ($vacancies as $vacancy) {
            $id = $vacancy->getId();
            if (null !== $id) {
                $byId[$id] = $vacancy;
            }
        }

        return new VacancyOverview(array_map(
            fn (int $id): VacancyOverviewVacancy => $this->overviewVacancy($byId[$id], $preferredSalary),
            $ids,
        ), $total, $page, VacancyOverviewFilter::PAGE_SIZE);
    }

    private function filteredQuery(int $userId, PreferredSalary $preferredSalary, VacancyOverviewFilter $filter): QueryBuilder
    {
        $builder = $this->entityManager->createQueryBuilder()
            ->from(Vacancy::class, 'vacancy')
            ->leftJoin('vacancy.company', 'company')
            ->leftJoin('vacancy.techStacks', 'techStack')
            ->where('IDENTITY(vacancy.user) = :userId')
            ->andWhere('vacancy.archived = :archived')
            ->setParameter('userId', $userId)
            ->setParameter('archived', $filter->archived);

        if (null !== $filter->query) {
            $builder->andWhere('(LOWER(vacancy.title) LIKE :query OR LOWER(company.name) LIKE :query OR LOWER(techStack.name) LIKE :query)')
                ->setParameter('query', '%'.mb_strtolower($filter->query).'%');
        }
        if (null !== $filter->status) {
            $builder->andWhere('vacancy.status = :status')->setParameter('status', $filter->status);
        }
        if (null !== $filter->excitement) {
            $builder->andWhere('vacancy.excitement = :excitement')->setParameter('excitement', $filter->excitement);
        }
        if (null !== $filter->workMode) {
            $builder->andWhere('vacancy.workMode = :workMode')->setParameter('workMode', $filter->workMode);
        }

        $this->applySalaryFit($builder, $preferredSalary, $filter->salaryFit);

        return $builder;
    }

    private function applySalaryFit(QueryBuilder $builder, PreferredSalary $preferredSalary, ?SalaryFit $salaryFit): void
    {
        if (null === $salaryFit) {
            return;
        }
        $target = $preferredSalary->getMinimumDecimal();
        if (null === $target || 'EUR' !== $preferredSalary->getCurrencyCode()) {
            $builder->andWhere(SalaryFit::Unknown === $salaryFit ? '1 = 1' : '1 = 0');

            return;
        }

        $meets = "vacancy.currencyCode = 'EUR' AND (vacancy.maximumSalary >= :salaryTarget OR (vacancy.maximumSalary IS NULL AND vacancy.minimumSalary >= :salaryTarget))";
        $below = "vacancy.currencyCode = 'EUR' AND vacancy.maximumSalary IS NOT NULL AND vacancy.maximumSalary < :salaryTarget";
        $unknown = "vacancy.currencyCode <> 'EUR' OR (vacancy.minimumSalary IS NULL AND vacancy.maximumSalary IS NULL) OR (vacancy.maximumSalary IS NULL AND (vacancy.minimumSalary IS NULL OR vacancy.minimumSalary < :salaryTarget))";
        $condition = match ($salaryFit) {
            SalaryFit::MeetsTarget => $meets,
            SalaryFit::BelowTarget => $below,
            SalaryFit::Unknown => $unknown,
        };
        $builder->andWhere('('.$condition.')')->setParameter('salaryTarget', $target);
    }

    private function overviewVacancy(Vacancy $vacancy, PreferredSalary $preferredSalary): VacancyOverviewVacancy
    {
        $id = $vacancy->getId() ?? throw new \LogicException('Overview vacancies must be persisted.');
        $range = $vacancy->getSalaryRange();
        $transitions = array_map(static fn ($transition): string => $transition->getName(), $this->workflow->getEnabledTransitions($vacancy));

        return new VacancyOverviewVacancy(
            $id,
            $vacancy->getTitle(),
            $vacancy->getCompany()?->getName(),
            $vacancy->getRecruiter()?->getAgencyName(),
            $vacancy->getWorkMode(),
            array_values($vacancy->getTechStacks()->map(static fn ($techStack): string => $techStack->getName())->toArray()),
            $vacancy->getExcitement(),
            $this->salaryLabel($range),
            $vacancy->getNextActionTitle(),
            $vacancy->getNextActionAt(),
            $vacancy->getStatus(),
            $this->salaryFit($range, $preferredSalary),
            $vacancy->isArchived(),
            array_values($transitions),
        );
    }

    private function salaryFit(?SalaryRange $range, PreferredSalary $preferredSalary): SalaryFit
    {
        $target = $preferredSalary->getMinimum();
        if (null === $range || null === $target || 'EUR' !== $range->getCurrencyCode() || 'EUR' !== $preferredSalary->getCurrencyCode()) {
            return SalaryFit::Unknown;
        }
        $maximum = $range->getMaximum();
        if (null !== $maximum && $maximum->isLessThan($target)) {
            return SalaryFit::BelowTarget;
        }
        if ((null !== $maximum && $maximum->isGreaterThanOrEqualTo($target)) || (null === $maximum && null !== $range->getMinimum() && $range->getMinimum()->isGreaterThanOrEqualTo($target))) {
            return SalaryFit::MeetsTarget;
        }

        return SalaryFit::Unknown;
    }

    private function salaryLabel(?SalaryRange $range): ?string
    {
        if (null === $range) {
            return null;
        }
        $minimum = $range->getMinimum()?->getAmount()->toString();
        $maximum = $range->getMaximum()?->getAmount()->toString();

        return $range->getCurrencyCode().' '.($minimum ?? '—').(null === $maximum ? '' : ' – '.$maximum);
    }
}
