<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Company;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(CompanyRepository::class)]
final readonly class DoctrineCompanyRepository implements CompanyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(string $companyId, string $userId): ?Company
    {
        if (!Uuid::isValid($companyId) || !Uuid::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(Company::class)->findOneBy([
            'id' => Uuid::fromString($companyId),
            'user' => Uuid::fromString($userId),
        ]);
    }

    public function searchOwnedBy(string $userId, string $query): array
    {
        if (!Uuid::isValid($userId)) {
            return [];
        }

        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT company')
            ->from(Company::class, 'company')
            ->leftJoin('company.directContacts', 'contact')
            ->where('IDENTITY(company.user) = :userId')
            ->setParameter('userId', Uuid::fromString($userId), UuidType::NAME)
            ->orderBy('company.name', 'ASC');

        if ('' !== $query) {
            $builder
                ->andWhere('(LOWER(company.name) LIKE :query OR LOWER(company.industry) LIKE :query OR LOWER(contact.name) LIKE :query OR LOWER(contact.email) LIKE :query)')
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
