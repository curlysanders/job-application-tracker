<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the temporary preferred salary currency default after backfilling existing users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ALTER min_preferred_salary_currency DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ALTER min_preferred_salary_currency SET DEFAULT 'EUR'");
    }
}
