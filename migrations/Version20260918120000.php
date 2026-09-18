<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add salary, commute, and transport preferences to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD min_preferred_salary NUMERIC(10, 2) DEFAULT NULL, ADD max_commute_minutes INT DEFAULT NULL, ADD preferred_transport_mode VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP min_preferred_salary, DROP max_commute_minutes, DROP preferred_transport_mode');
    }
}
