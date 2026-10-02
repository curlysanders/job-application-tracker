<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change outbox domain events to JSON documents.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE outbox MODIFY domainEvent JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        throw new \LogicException('JSON outbox records cannot safely be converted back to PHP serialized objects.');
    }
}
