<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260921120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add active resume metadata to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD resume_storage_path VARCHAR(255) DEFAULT NULL, ADD resume_original_filename VARCHAR(255) DEFAULT NULL, ADD resume_mime_type VARCHAR(100) DEFAULT NULL, ADD resume_uploaded_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP resume_storage_path, DROP resume_original_filename, DROP resume_mime_type, DROP resume_uploaded_at');
    }
}
