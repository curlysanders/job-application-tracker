<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Optimize vacancy dashboard, pipeline, and reminder queries with composite indexes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vacancies DROP INDEX IDX_VACANCIES_STATUS, DROP INDEX IDX_VACANCIES_USER, DROP INDEX IDX_VACANCIES_DATE_ADDED, ADD INDEX IDX_VACANCIES_DASHBOARD (user_id, archived, date_added), ADD INDEX IDX_VACANCIES_DASHBOARD_STATUS (user_id, archived, status, date_added), ADD INDEX IDX_VACANCIES_REMINDERS (user_id, archived, next_action_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vacancies DROP INDEX IDX_VACANCIES_DASHBOARD, DROP INDEX IDX_VACANCIES_DASHBOARD_STATUS, DROP INDEX IDX_VACANCIES_REMINDERS, ADD INDEX IDX_VACANCIES_STATUS (status), ADD INDEX IDX_VACANCIES_USER (user_id), ADD INDEX IDX_VACANCIES_DATE_ADDED (date_added)');
    }
}
