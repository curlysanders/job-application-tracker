<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store encrypted, tenant-owned vacancy authoring drafts created from reviewed extraction proposals.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vacancy_authoring_drafts (id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, ciphertext LONGTEXT NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_VACANCY_AUTHORING_DRAFTS_OWNER_EXPIRY (user_id, expires_at), INDEX IDX_VACANCY_AUTHORING_DRAFTS_EXPIRY (expires_at), PRIMARY KEY(id), CONSTRAINT FK_VACANCY_AUTHORING_DRAFTS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vacancy_authoring_drafts');
    }
}
