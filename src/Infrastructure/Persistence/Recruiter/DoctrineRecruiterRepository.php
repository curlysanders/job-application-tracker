<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(RecruiterRepository::class)]
final readonly class DoctrineRecruiterRepository implements RecruiterRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(string $recruiterId, string $userId): ?Recruiter
    {
        if (!Uuid::isValid($recruiterId) || !Uuid::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(Recruiter::class)->findOneBy([
            'id' => Uuid::fromString($recruiterId),
            'user' => Uuid::fromString($userId),
        ]);
    }

    public function searchOwnedBy(string $userId, string $query): array
    {
        if (!Uuid::isValid($userId)) {
            return [];
        }

        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT recruiter')
            ->from(Recruiter::class, 'recruiter')
            ->leftJoin('recruiter.directContacts', 'contact')
            ->where('IDENTITY(recruiter.user) = :userId')
            ->setParameter('userId', Uuid::fromString($userId), UuidType::NAME)
            ->orderBy('recruiter.agencyName', 'ASC');

        if ('' !== $query) {
            $builder
                ->andWhere('(LOWER(recruiter.agencyName) LIKE :query OR LOWER(contact.name) LIKE :query OR LOWER(contact.email) LIKE :query)')
                ->setParameter('query', '%'.mb_strtolower($query).'%');
        }

        /** @var list<Recruiter> $recruiters */
        $recruiters = $builder->getQuery()->getResult();

        return $recruiters;
    }

    public function save(Recruiter $recruiter): void
    {
        $this->entityManager->persist($recruiter);
        $this->entityManager->flush();
    }
}
