<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add idempotent status-history delivery records and staged resume validation state.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vacancy_status_history ADD outbox_record_id INT DEFAULT NULL, ADD UNIQUE INDEX UNIQ_VACANCY_STATUS_HISTORY_OUTBOX_RECORD (outbox_record_id)');
        $this->addSql('ALTER TABLE users ADD pending_resume_storage_path VARCHAR(255) DEFAULT NULL, ADD pending_resume_original_filename VARCHAR(255) DEFAULT NULL, ADD pending_resume_mime_type VARCHAR(100) DEFAULT NULL, ADD pending_resume_uploaded_at DATETIME DEFAULT NULL, ADD resume_validation_id BINARY(16) DEFAULT NULL, ADD resume_validation_status VARCHAR(20) DEFAULT NULL, ADD resume_validation_failure VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE users SET resume_validation_status = 'validated' WHERE resume_storage_path IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        throw new \LogicException('Removing asynchronous audit and resume validation state would lose delivery and validation history. Restore a backup instead.');
    }
}
