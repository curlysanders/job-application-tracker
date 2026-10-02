<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace global Company and Recruiter name indexes with owner-scoped indexes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE companies DROP INDEX IDX_COMPANIES_NAME');
        $this->addSql('ALTER TABLE recruiters DROP INDEX IDX_RECRUITERS_AGENCY_NAME');
    }

    public function down(Schema $schema): void
    {
        throw new \LogicException('Restoring global lookup indexes would be inconsistent with user-owned Companies and Recruiters. Restore a backup instead.');
    }
}
