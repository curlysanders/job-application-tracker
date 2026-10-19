<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\IdentifierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(RecruiterRepository::class)]
final readonly class DoctrineRecruiterRepository implements RecruiterRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(
        string $recruiterId,
        string $userId,
    ): ?Recruiter {
        if (!Identifier::isValid($recruiterId) || !Identifier::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(Recruiter::class)->findOneBy([
            'id' => Identifier::fromString($recruiterId),
            'user' => Identifier::fromString($userId),
        ]);
    }

    public function searchOwnedBy(
        string $userId,
        string $query,
    ): array {
        if (!Identifier::isValid($userId)) {
            return [];
        }

        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT recruiter')
            ->from(Recruiter::class, 'recruiter')
            ->leftJoin('recruiter.directContacts', 'contact')
            ->where('IDENTITY(recruiter.user) = :userId')
            ->setParameter('userId', Identifier::fromString($userId), IdentifierType::NAME)
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
