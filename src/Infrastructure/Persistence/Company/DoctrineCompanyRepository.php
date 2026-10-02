<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Company;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(CompanyRepository::class)]
final readonly class DoctrineCompanyRepository implements CompanyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(string $id): ?Company
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Company::class, Uuid::fromString($id));
    }

    public function search(string $query): array
    {
        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT company')
            ->from(Company::class, 'company')
            ->leftJoin('company.directContacts', 'contact')
            ->orderBy('company.name', 'ASC');

        if ('' !== $query) {
            $builder
                ->where('LOWER(company.name) LIKE :query OR LOWER(company.industry) LIKE :query OR LOWER(contact.name) LIKE :query OR LOWER(contact.email) LIKE :query')
                ->setParameter('query', '%'.mb_strtolower($query).'%');
        }

        /** @var list<Company> $companies */
        $companies = $builder->getQuery()->getResult();

        return $companies;
    }

    public function save(Company $company): void
    {
        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }
}
