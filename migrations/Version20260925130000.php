<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use readable vacancy indexes and technology-stack foreign key names.';
    }

    public function up(Schema $schema): void
    {
        $this->renameIndex('vacancies', 'IDX_99165A59979B1AD6', 'IDX_VACANCIES_COMPANY');
        $this->renameIndex('vacancies', 'IDX_99165A59156BE243', 'IDX_VACANCIES_RECRUITER');
        $this->renameLegacyIndex('vacancy_tech_stacks', 'IDX_VACANCY_TECH_STACKS_VACANCY', 'IDX_610876F3433B78C4');
        $this->renameLegacyIndex('vacancy_tech_stacks', 'IDX_VACANCY_TECH_STACKS_TECH_STACK', 'IDX_610876F331C0AE9B');

        $this->renameForeignKey('FK_610876F3433B78C4', 'FK_VACANCY_TECH_STACKS_VACANCY', 'vacancy_id', 'vacancies');
        $this->renameForeignKey('FK_610876F331C0AE9B', 'FK_VACANCY_TECH_STACKS_TECH_STACK', 'tech_stack_id', 'tech_stacks');
    }

    public function down(Schema $schema): void
    {
        // The physical names do not affect schema semantics, so no reverse rename is needed.
    }

    /** @param non-empty-string $table */
    private function renameLegacyIndex(string $table, string $legacyName, string $expectedName): void
    {
        $indexNames = $this->indexNames($table);
        if (isset($indexNames[strtolower($legacyName)]) && !isset($indexNames[strtolower($expectedName)])) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME INDEX %s TO %s', $table, $legacyName, $expectedName));
        }
    }

    /** @param non-empty-string $table */
    private function renameIndex(string $table, string $generatedName, string $readableName): void
    {
        $indexNames = $this->indexNames($table);
        if (isset($indexNames[strtolower($generatedName)]) && !isset($indexNames[strtolower($readableName)])) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME INDEX %s TO %s', $table, $generatedName, $readableName));
        }
    }

    /**
     * @param non-empty-string $column
     * @param non-empty-string $referencedTable
     */
    private function renameForeignKey(string $generatedName, string $readableName, string $column, string $referencedTable): void
    {
        $foreignKeyNames = $this->foreignKeyNames('vacancy_tech_stacks');
        if (isset($foreignKeyNames[strtolower($generatedName)]) && !isset($foreignKeyNames[strtolower($readableName)])) {
            $this->addSql(sprintf('ALTER TABLE vacancy_tech_stacks DROP FOREIGN KEY %s', $generatedName));
            $this->addSql(sprintf(
                'ALTER TABLE vacancy_tech_stacks ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s (id) ON DELETE CASCADE',
                $readableName,
                $column,
                $referencedTable,
            ));
        }
    }

    /**
     * @param non-empty-string $table
     *
     * @return array<string, true>
     */
    private function indexNames(string $table): array
    {
        $names = [];
        foreach ($this->connection->fetchFirstColumn(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        ) as $name) {
            if (is_string($name)) {
                $names[strtolower($name)] = true;
            }
        }

        return $names;
    }

    /**
     * @param non-empty-string $table
     *
     * @return array<string, true>
     */
    private function foreignKeyNames(string $table): array
    {
        $names = [];
        foreach ($this->connection->fetchFirstColumn(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table],
        ) as $name) {
            if (is_string($name)) {
                $names[strtolower($name)] = true;
            }
        }

        return $names;
    }
}
