<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Console;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:doctrine:schema:assert-synchronized', description: 'Fail when Doctrine proposes schema changes outside the known Lingoda Carbon timestamp comparison.')]
final class AssertDoctrineSchemaCommand extends Command
{
    /** @var list<string> */
    private const array EXPECTED_LINGODA_CARBON_DIFFS = [
        'ALTER TABLE outbox CHANGE occurredAt occurredAt DATETIME(6) NOT NULL, CHANGE publishedOn publishedOn DATETIME(6) DEFAULT NULL, CHANGE claimedAt claimedAt DATETIME(6) DEFAULT NULL',
    ];

    /** @var list<string> */
    private const array UNMAPPED_TABLE_DIFFS = [
        'DROP TABLE doctrine_migration_versions',
        'DROP TABLE sessions',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sql = new SchemaTool($this->entityManager)->getUpdateSchemaSql($this->entityManager->getMetadataFactory()->getAllMetadata());
        $entitySchemaSql = array_values(array_filter(
            $sql,
            static fn (string $statement): bool => !in_array($statement, self::UNMAPPED_TABLE_DIFFS, true),
        ));
        $unexpected = array_values(array_diff($entitySchemaSql, self::EXPECTED_LINGODA_CARBON_DIFFS));

        if ([] !== $unexpected) {
            $io->error(['Doctrine found unexpected schema changes:', ...$unexpected]);

            return Command::FAILURE;
        }

        $io->success([] === $sql ? 'Doctrine schema is synchronized.' : 'Doctrine schema is synchronized apart from the known Lingoda Carbon timestamp comparison.');

        return Command::SUCCESS;
    }
}
