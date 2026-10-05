<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Health;

use CurlySanders\JobApplicationTracker\Application\Health\ApplicationHealthCheck;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ApplicationHealthCheck::class)]
final readonly class DoctrineApplicationHealthCheck implements ApplicationHealthCheck
{
    public function __construct(private Connection $connection)
    {
    }

    public function isReady(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1')->free();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
