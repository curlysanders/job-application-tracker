<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the ISO currency of each preferred salary.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE users ADD min_preferred_salary_currency VARCHAR(3) NOT NULL DEFAULT 'EUR'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP min_preferred_salary_currency');
    }
}
