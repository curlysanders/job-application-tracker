<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the managed technology stack catalogue.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tech_stacks (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, category VARCHAR(255) NOT NULL, INDEX IDX_TECH_STACKS_CATEGORY (category), UNIQUE INDEX UNIQ_TECH_STACKS_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tech_stacks');
    }
}
