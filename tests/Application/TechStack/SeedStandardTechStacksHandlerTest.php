<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\TechStack;

use CurlySanders\JobApplicationTracker\Application\TechStack\Command\SeedStandardTechStacks;
use CurlySanders\JobApplicationTracker\Application\TechStack\Command\SeedStandardTechStacksHandler;
use CurlySanders\JobApplicationTracker\Application\TechStack\PaginatedResults;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use PHPUnit\Framework\TestCase;

final class SeedStandardTechStacksHandlerTest extends TestCase
{
    public function testSeedsTheCatalogueOnceWithoutOverwritingExistingTags(): void
    {
        $repository = new InMemoryTechStackRepository();
        $repository->save(new TechStack('PHP', 'Custom category'));
        $handler = new SeedStandardTechStacksHandler($repository);

        $created = $handler(new SeedStandardTechStacks());

        self::assertSame(81, $created);
        self::assertSame('Custom category', $repository->findBySlug('php')?->getCategory());
        self::assertSame('Architecture & Patterns', $repository->findBySlug('domain-driven-design-ddd')?->getCategory());
        self::assertSame('Architecture & Patterns', $repository->findBySlug('event-driven-architecture-eda')?->getCategory());
        self::assertSame('Queuing & Messaging', $repository->findBySlug('apache-kafka')?->getCategory());
        self::assertSame('OS & Platforms', $repository->findBySlug('macos')?->getCategory());
        self::assertSame('API & Integration', $repository->findBySlug('openapi')?->getCategory());
        self::assertSame('Relational Databases', $repository->findBySlug('microsoft-sql-server')?->getCategory());
        self::assertSame('NoSQL Databases', $repository->findBySlug('amazon-dynamodb')?->getCategory());
        self::assertSame('Search & Caching', $repository->findBySlug('opensearch')?->getCategory());
        self::assertSame(0, $handler(new SeedStandardTechStacks()));
        self::assertCount(82, $repository->all());
    }
}

final class InMemoryTechStackRepository implements TechStackRepository
{
    /** @var array<string, TechStack> */
    private array $techStacks = [];

    public function findBySlug(string $slug): ?TechStack
    {
        return $this->techStacks[$slug] ?? null;
    }

    public function search(string $query, int $page = 1): PaginatedResults
    {
        return new PaginatedResults([], false);
    }

    public function categories(string $query, int $page = 1): PaginatedResults
    {
        return new PaginatedResults([], false);
    }

    public function save(TechStack $techStack): void
    {
        $this->techStacks[$techStack->getSlug()] = $techStack;
    }

    /** @return array<string, TechStack> */
    public function all(): array
    {
        return $this->techStacks;
    }
}
