<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add immutable vacancy status transition history.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vacancy_status_history (id INT AUTO_INCREMENT NOT NULL, vacancy_id INT NOT NULL, from_status VARCHAR(20) NOT NULL, to_status VARCHAR(20) NOT NULL, notes LONGTEXT DEFAULT NULL, transitioned_at DATETIME NOT NULL, INDEX IDX_VACANCY_STATUS_HISTORY_VACANCY_AT (vacancy_id, transitioned_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vacancy_status_history ADD CONSTRAINT FK_VACANCY_STATUS_HISTORY_VACANCY FOREIGN KEY (vacancy_id) REFERENCES vacancies (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vacancy_status_history');
    }
}
