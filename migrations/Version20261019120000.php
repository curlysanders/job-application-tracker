<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store encrypted, tenant-owned asynchronous AI analysis requests and results.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE analysis_requests (id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, kind VARCHAR(30) NOT NULL, input_ciphertext LONGTEXT NOT NULL, input_fingerprint VARCHAR(64) NOT NULL, provider VARCHAR(30) NOT NULL, model VARCHAR(120) NOT NULL, version INT NOT NULL, state VARCHAR(20) NOT NULL, failure_code VARCHAR(50) DEFAULT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_ANALYSIS_REQUESTS_OWNER_EXPIRY (user_id, expires_at), INDEX IDX_ANALYSIS_REQUESTS_EXPIRY (expires_at), PRIMARY KEY(id), CONSTRAINT FK_ANALYSIS_REQUESTS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE analysis_results (id BINARY(16) NOT NULL, request_id BINARY(16) NOT NULL, ciphertext LONGTEXT NOT NULL, fingerprint VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_ANALYSIS_RESULTS_REQUEST (request_id), PRIMARY KEY(id), CONSTRAINT FK_ANALYSIS_RESULTS_REQUEST FOREIGN KEY (request_id) REFERENCES analysis_requests (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE analysis_results');
        $this->addSql('DROP TABLE analysis_requests');
    }
}
