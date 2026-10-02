<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20260930120000 extends AbstractMigration
{
    /** @var list<string> */
    private const array ROOT_TABLES = ['users', 'companies', 'recruiters', 'tech_stacks', 'vacancies'];

    public function getDescription(): string
    {
        return 'Convert aggregate roots and relations from integer identifiers to binary UUIDv7 identifiers.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->relationsUseBinaryUuids()) {
            foreach (self::ROOT_TABLES as $table) {
                $this->ensureRootUuidV7($table);
            }

            foreach ([
                ['direct_contacts', 'company_uuid'], ['direct_contacts', 'recruiter_uuid'],
                ['vacancies', 'user_uuid'], ['vacancies', 'company_uuid'], ['vacancies', 'recruiter_uuid'],
                ['vacancy_tech_stacks', 'vacancy_uuid'], ['vacancy_tech_stacks', 'tech_stack_uuid'],
                ['vacancy_status_history', 'vacancy_uuid'],
            ] as [$table, $column]) {
                $this->ensureColumn($table, $column, 'BINARY(16) DEFAULT NULL');
            }

            if (!$this->temporaryMappingsAreComplete()) {
                if ($this->hasConvertedRoot()) {
                    throw new \LogicException('The UUID upgrade was interrupted after a root identifier changed before all temporary relation mappings were complete. Restore the database backup before retrying.');
                }

                $this->copyRelationUuids();
            }

            $this->assertTemporaryMappingsAreComplete();
            $this->dropReferencingForeignKeys();
            $this->replaceRootPrimaryKeys();
            $this->replaceRelationColumns();
        }

        $this->ensureRelationIndexes();
        $this->createForeignKeys();
    }

    public function down(Schema $schema): void
    {
        throw new \LogicException('This data-preserving identifier migration cannot safely be reversed. Restore a backup instead.');
    }

    private function ensureRootUuidV7(string $table): void
    {
        if ($this->isBinaryUuidColumn($table, 'id') && !$this->hasColumn($table, 'uuid')) {
            return;
        }
        $this->ensureColumn($table, 'uuid', 'BINARY(16) DEFAULT NULL');
        foreach ($this->connection->iterateAssociative(sprintf('SELECT id, uuid FROM %s ORDER BY id ASC', $table)) as $row) {
            if (null === $row['uuid']) {
                $this->connection->executeStatement(sprintf('UPDATE %s SET uuid = :uuid WHERE id = :id', $table), ['uuid' => Uuid::v7()->toBinary(), 'id' => $row['id']]);
            }
        }
        foreach ($this->connection->iterateAssociative(sprintf('SELECT uuid FROM %s', $table)) as $row) {
            $uuid = $row['uuid'];
            if (!is_string($uuid) || 16 !== strlen($uuid) || '7' !== Uuid::fromBinary($uuid)->toRfc4122()[14]) {
                throw new \LogicException(sprintf('Temporary UUIDs for %s must all be UUIDv7 values.', $table));
            }
        }
    }

    private function copyRelationUuids(): void
    {
        if ($this->hasColumn('direct_contacts', 'company_id')) {
            $this->execute('UPDATE direct_contacts contact LEFT JOIN companies company ON company.id = contact.company_id LEFT JOIN recruiters recruiter ON recruiter.id = contact.recruiter_id SET contact.company_uuid = COALESCE(contact.company_uuid, company.uuid), contact.recruiter_uuid = COALESCE(contact.recruiter_uuid, recruiter.uuid)');
        }
        if ($this->hasColumn('vacancies', 'user_id')) {
            $this->execute('UPDATE vacancies vacancy LEFT JOIN users user ON user.id = vacancy.user_id LEFT JOIN companies company ON company.id = vacancy.company_id LEFT JOIN recruiters recruiter ON recruiter.id = vacancy.recruiter_id SET vacancy.user_uuid = COALESCE(vacancy.user_uuid, user.uuid), vacancy.company_uuid = COALESCE(vacancy.company_uuid, company.uuid), vacancy.recruiter_uuid = COALESCE(vacancy.recruiter_uuid, recruiter.uuid)');
        }
        if ($this->hasColumn('vacancy_tech_stacks', 'vacancy_id')) {
            $this->execute('UPDATE vacancy_tech_stacks relation_table INNER JOIN vacancies vacancy ON vacancy.id = relation_table.vacancy_id INNER JOIN tech_stacks tech_stack ON tech_stack.id = relation_table.tech_stack_id SET relation_table.vacancy_uuid = COALESCE(relation_table.vacancy_uuid, vacancy.uuid), relation_table.tech_stack_uuid = COALESCE(relation_table.tech_stack_uuid, tech_stack.uuid)');
        }
        if ($this->hasColumn('vacancy_status_history', 'vacancy_id')) {
            $this->execute('UPDATE vacancy_status_history history INNER JOIN vacancies vacancy ON vacancy.id = history.vacancy_id SET history.vacancy_uuid = COALESCE(history.vacancy_uuid, vacancy.uuid)');
        }
    }

    private function assertTemporaryMappingsAreComplete(): void
    {
        if (!$this->temporaryMappingsAreComplete()) {
            throw new \LogicException('A legacy relation could not be mapped to a UUIDv7 value.');
        }
    }

    private function temporaryMappingsAreComplete(): bool
    {
        foreach ([
            'SELECT COUNT(*) FROM direct_contacts WHERE company_id IS NOT NULL AND company_uuid IS NULL',
            'SELECT COUNT(*) FROM direct_contacts WHERE recruiter_id IS NOT NULL AND recruiter_uuid IS NULL',
            'SELECT COUNT(*) FROM vacancies WHERE user_uuid IS NULL',
            'SELECT COUNT(*) FROM vacancies WHERE company_id IS NOT NULL AND company_uuid IS NULL',
            'SELECT COUNT(*) FROM vacancies WHERE recruiter_id IS NOT NULL AND recruiter_uuid IS NULL',
            'SELECT COUNT(*) FROM vacancy_tech_stacks WHERE vacancy_uuid IS NULL OR tech_stack_uuid IS NULL',
            'SELECT COUNT(*) FROM vacancy_status_history WHERE vacancy_uuid IS NULL',
        ] as $sql) {
            $count = $this->connection->fetchOne($sql);

            if ((!is_int($count) && !is_string($count)) || 0 !== (int) $count) {
                return false;
            }
        }

        return true;
    }

    private function dropReferencingForeignKeys(): void
    {
        foreach (['direct_contacts', 'vacancies', 'vacancy_tech_stacks', 'vacancy_status_history'] as $table) {
            foreach ($this->foreignKeys($table) as $foreignKey) {
                $this->execute(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $foreignKey));
            }
        }
    }

    private function replaceRootPrimaryKeys(): void
    {
        foreach (self::ROOT_TABLES as $table) {
            if (!$this->isBinaryUuidColumn($table, 'id')) {
                $this->execute(sprintf('ALTER TABLE %s DROP PRIMARY KEY, DROP COLUMN id, CHANGE uuid id BINARY(16) NOT NULL, ADD PRIMARY KEY (id)', $table));
            }
        }
    }

    private function replaceRelationColumns(): void
    {
        if (!$this->isBinaryUuidColumn('direct_contacts', 'company_id')) {
            $this->execute('ALTER TABLE direct_contacts DROP COLUMN company_id, DROP COLUMN recruiter_id, CHANGE company_uuid company_id BINARY(16) DEFAULT NULL, CHANGE recruiter_uuid recruiter_id BINARY(16) DEFAULT NULL');
        }
        if (!$this->isBinaryUuidColumn('vacancies', 'user_id')) {
            $this->execute('ALTER TABLE vacancies DROP COLUMN user_id, DROP COLUMN company_id, DROP COLUMN recruiter_id, CHANGE user_uuid user_id BINARY(16) NOT NULL, CHANGE company_uuid company_id BINARY(16) DEFAULT NULL, CHANGE recruiter_uuid recruiter_id BINARY(16) DEFAULT NULL');
        }
        if (!$this->isBinaryUuidColumn('vacancy_tech_stacks', 'vacancy_id')) {
            $this->execute('ALTER TABLE vacancy_tech_stacks DROP PRIMARY KEY, DROP COLUMN vacancy_id, DROP COLUMN tech_stack_id, CHANGE vacancy_uuid vacancy_id BINARY(16) NOT NULL, CHANGE tech_stack_uuid tech_stack_id BINARY(16) NOT NULL, ADD PRIMARY KEY (vacancy_id, tech_stack_id)');
        }
        if (!$this->isBinaryUuidColumn('vacancy_status_history', 'vacancy_id')) {
            $this->execute('ALTER TABLE vacancy_status_history DROP COLUMN vacancy_id, CHANGE vacancy_uuid vacancy_id BINARY(16) NOT NULL');
        }
    }

    private function ensureRelationIndexes(): void
    {
        $this->ensureIndex('direct_contacts', 'IDX_DIRECT_CONTACT_COMPANY', 'company_id');
        $this->ensureIndex('direct_contacts', 'IDX_DIRECT_CONTACT_RECRUITER', 'recruiter_id');
        $this->ensureIndex('vacancies', 'IDX_VACANCIES_USER', 'user_id');
        $this->ensureIndex('vacancies', 'IDX_VACANCIES_COMPANY', 'company_id');
        $this->ensureIndex('vacancies', 'IDX_VACANCIES_RECRUITER', 'recruiter_id');
        $this->ensureIndex('vacancy_tech_stacks', 'IDX_610876F331C0AE9B', 'tech_stack_id');
        $this->ensureIndex('vacancy_status_history', 'IDX_98A6E13433B78C4', 'vacancy_id');
        $this->ensureIndex('vacancy_status_history', 'IDX_VACANCY_STATUS_HISTORY_VACANCY_AT', 'vacancy_id, transitioned_at');
    }

    private function createForeignKeys(): void
    {
        $foreignKeys = [
            'direct_contacts' => ['FK_DIRECT_CONTACT_COMPANY' => 'FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE', 'FK_DIRECT_CONTACT_RECRUITER' => 'FOREIGN KEY (recruiter_id) REFERENCES recruiters (id) ON DELETE CASCADE'],
            'vacancies' => ['FK_VACANCIES_USER' => 'FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE', 'FK_VACANCIES_COMPANY' => 'FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL', 'FK_VACANCIES_RECRUITER' => 'FOREIGN KEY (recruiter_id) REFERENCES recruiters (id) ON DELETE SET NULL'],
            'vacancy_tech_stacks' => ['FK_VACANCY_TECH_STACKS_VACANCY' => 'FOREIGN KEY (vacancy_id) REFERENCES vacancies (id) ON DELETE CASCADE', 'FK_VACANCY_TECH_STACKS_TECH_STACK' => 'FOREIGN KEY (tech_stack_id) REFERENCES tech_stacks (id) ON DELETE CASCADE'],
            'vacancy_status_history' => ['FK_VACANCY_STATUS_HISTORY_VACANCY' => 'FOREIGN KEY (vacancy_id) REFERENCES vacancies (id) ON DELETE CASCADE'],
        ];
        foreach ($foreignKeys as $table => $definitions) {
            $existing = $this->foreignKeys($table);
            foreach ($definitions as $name => $definition) {
                if (!in_array($name, $existing, true)) {
                    $this->execute(sprintf('ALTER TABLE %s ADD CONSTRAINT %s %s', $table, $name, $definition));
                }
            }
        }
    }

    /** @return list<string> */
    private function foreignKeys(string $table): array
    {
        return array_values(array_filter($this->connection->fetchFirstColumn('SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND REFERENCED_TABLE_NAME IS NOT NULL', ['table' => $table]), 'is_string'));
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        if (!$this->hasColumn($table, $column)) {
            $this->execute(sprintf('ALTER TABLE %s ADD %s %s', $table, $column, $definition));
        }
    }

    private function ensureIndex(string $table, string $index, string $columns): void
    {
        $expectedColumns = array_map('trim', explode(',', $columns));
        $actualColumns = $this->indexColumns($table, $index);

        if ([] !== $actualColumns) {
            if ($expectedColumns === $actualColumns) {
                return;
            }

            $this->execute(sprintf('ALTER TABLE %s DROP INDEX %s', $table, $index));
        }

        foreach ($this->connection->fetchFirstColumn('SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME <> :primary', ['table' => $table, 'primary' => 'PRIMARY']) as $existingIndex) {
            if (!is_string($existingIndex) || $expectedColumns !== $this->indexColumns($table, $existingIndex)) {
                continue;
            }

            $this->execute(sprintf('ALTER TABLE %s RENAME INDEX %s TO %s', $table, $existingIndex, $index));

            return;
        }

        $this->execute(sprintf('ALTER TABLE %s ADD INDEX %s (%s)', $table, $index, $columns));
    }

    /** @return list<string> */
    private function indexColumns(string $table, string $index): array
    {
        return array_values(array_filter($this->connection->fetchFirstColumn('SELECT COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index ORDER BY SEQ_IN_INDEX', ['table' => $table, 'index' => $index]), 'is_string'));
    }

    private function hasColumn(string $table, string $column): bool
    {
        return false !== $this->connection->fetchOne('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column', ['table' => $table, 'column' => $column]);
    }

    private function isBinaryUuidColumn(string $table, string $column): bool
    {
        return 'binary(16)' === $this->connection->fetchOne('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column', ['table' => $table, 'column' => $column]);
    }

    private function relationsUseBinaryUuids(): bool
    {
        foreach ([
            ['direct_contacts', 'company_id'], ['direct_contacts', 'recruiter_id'],
            ['vacancies', 'user_id'], ['vacancies', 'company_id'], ['vacancies', 'recruiter_id'],
            ['vacancy_tech_stacks', 'vacancy_id'], ['vacancy_tech_stacks', 'tech_stack_id'],
            ['vacancy_status_history', 'vacancy_id'],
        ] as [$table, $column]) {
            if (!$this->isBinaryUuidColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasConvertedRoot(): bool
    {
        foreach (self::ROOT_TABLES as $table) {
            if ($this->isBinaryUuidColumn($table, 'id')) {
                return true;
            }
        }

        return false;
    }

    private function execute(string $sql): void
    {
        $this->connection->executeStatement($sql);
    }
}
