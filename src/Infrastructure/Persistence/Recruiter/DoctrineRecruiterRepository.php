<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(RecruiterRepository::class)]
final readonly class DoctrineRecruiterRepository implements RecruiterRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function find(string $id): ?Recruiter
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->entityManager->find(Recruiter::class, Uuid::fromString($id));
    }

    public function search(string $query): array
    {
        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT recruiter')
            ->from(Recruiter::class, 'recruiter')
            ->leftJoin('recruiter.directContacts', 'contact')
            ->orderBy('recruiter.agencyName', 'ASC');

        if ('' !== $query) {
            $builder
                ->where('LOWER(recruiter.agencyName) LIKE :query OR LOWER(contact.name) LIKE :query OR LOWER(contact.email) LIKE :query')
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
