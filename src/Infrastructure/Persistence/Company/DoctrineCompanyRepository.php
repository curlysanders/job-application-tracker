<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Company;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\IdentifierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(CompanyRepository::class)]
final readonly class DoctrineCompanyRepository implements CompanyRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(string $companyId, string $userId): ?Company
    {
        if (!Identifier::isValid($companyId) || !Identifier::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(Company::class)->findOneBy([
            'id' => Identifier::fromString($companyId),
            'user' => Identifier::fromString($userId),
        ]);
    }

    public function searchOwnedBy(string $userId, string $query): array
    {
        if (!Identifier::isValid($userId)) {
            return [];
        }

        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT company')
            ->from(Company::class, 'company')
            ->leftJoin('company.directContacts', 'contact')
            ->where('IDENTITY(company.user) = :userId')
            ->setParameter('userId', Identifier::fromString($userId), IdentifierType::NAME)
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
