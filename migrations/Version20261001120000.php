<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the transactional domain-events outbox table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE outbox (id BIGINT AUTO_INCREMENT NOT NULL, eventType VARCHAR(255) NOT NULL, domainEvent LONGBLOB NOT NULL, entityId VARCHAR(36) NOT NULL, occurredAt DATETIME(6) NOT NULL, publishedOn DATETIME(6) DEFAULT NULL, claimedAt DATETIME(6) DEFAULT NULL, INDEX entity_type_published_idx (entityId, eventType, publishedOn), INDEX idx_unpublished_occurred (publishedOn, occurredAt), INDEX idx_unpublished_claimed_occurred (publishedOn, claimedAt, occurredAt), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox');
    }
}
