<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\TechStack;

use CurlySanders\JobApplicationTracker\Application\TechStack\PaginatedResults;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TechStackRepository::class)]
final readonly class DoctrineTechStackRepository implements TechStackRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findBySlug(string $slug): ?TechStack
    {
        return $this->entityManager->getRepository(TechStack::class)->findOneBy(['slug' => $slug]);
    }

    public function search(string $query, int $page = 1): PaginatedResults
    {
        $page = max(1, $page);
        $builder = $this->entityManager->createQueryBuilder()
            ->select('techStack')
            ->from(TechStack::class, 'techStack')
            ->orderBy('techStack.name', 'ASC')
            ->addOrderBy('techStack.id', 'ASC')
            ->setFirstResult(($page - 1) * TechStackRepository::AUTOCOMPLETE_PAGE_SIZE)
            ->setMaxResults(TechStackRepository::AUTOCOMPLETE_PAGE_SIZE + 1);

        if ('' !== $query) {
            $builder
                ->where('LOWER(techStack.name) LIKE :query OR LOWER(techStack.category) LIKE :query')
                ->setParameter('query', '%'.mb_strtolower($query).'%');
        }

        /** @var list<TechStack> $techStacks */
        $techStacks = $builder->getQuery()->getResult();

        return new PaginatedResults(
            array_slice($techStacks, 0, TechStackRepository::AUTOCOMPLETE_PAGE_SIZE),
            count($techStacks) > TechStackRepository::AUTOCOMPLETE_PAGE_SIZE,
        );
    }

    public function categories(string $query, int $page = 1): PaginatedResults
    {
        $page = max(1, $page);
        $builder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT techStack.category')
            ->from(TechStack::class, 'techStack')
            ->orderBy('techStack.category', 'ASC')
            ->setFirstResult(($page - 1) * TechStackRepository::AUTOCOMPLETE_PAGE_SIZE)
            ->setMaxResults(TechStackRepository::AUTOCOMPLETE_PAGE_SIZE + 1);

        if ('' !== $query) {
            $builder
                ->where('LOWER(techStack.category) LIKE :query')
                ->setParameter('query', '%'.mb_strtolower($query).'%');
        }

        /** @var list<string> $categories */
        $categories = $builder->getQuery()->getSingleColumnResult();

        return new PaginatedResults(
            array_slice($categories, 0, TechStackRepository::AUTOCOMPLETE_PAGE_SIZE),
            count($categories) > TechStackRepository::AUTOCOMPLETE_PAGE_SIZE,
        );
    }

    public function save(TechStack $techStack): void
    {
        $this->entityManager->persist($techStack);
        $this->entityManager->flush();
    }
}
