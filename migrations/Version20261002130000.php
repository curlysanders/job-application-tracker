<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make companies and recruiters user-owned from their uniquely linked vacancy owner.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->assertEveryRecordHasOneVacancyOwner('companies', 'company_id');
        $this->assertEveryRecordHasOneVacancyOwner('recruiters', 'recruiter_id');

        $this->addSql('ALTER TABLE companies ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE recruiters ADD user_id BINARY(16) DEFAULT NULL');
        $this->addSql('UPDATE companies company INNER JOIN vacancies vacancy ON vacancy.company_id = company.id SET company.user_id = vacancy.user_id');
        $this->addSql('UPDATE recruiters recruiter INNER JOIN vacancies vacancy ON vacancy.recruiter_id = recruiter.id SET recruiter.user_id = vacancy.user_id');
        $this->addSql('ALTER TABLE companies MODIFY user_id BINARY(16) NOT NULL, ADD INDEX IDX_COMPANIES_USER_NAME (user_id, name), ADD CONSTRAINT FK_COMPANIES_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recruiters MODIFY user_id BINARY(16) NOT NULL, ADD INDEX IDX_RECRUITERS_USER_AGENCY_NAME (user_id, agency_name), ADD CONSTRAINT FK_RECRUITERS_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        throw new \LogicException('Removing Company and Recruiter ownership would reintroduce cross-user data exposure. Restore a backup instead.');
    }

    private function assertEveryRecordHasOneVacancyOwner(string $table, string $vacancyOwnerColumn): void
    {
        $ids = $this->connection->fetchFirstColumn(sprintf(
            'SELECT HEX(aggregate_root.id) FROM %1$s aggregate_root LEFT JOIN vacancies vacancy ON vacancy.%2$s = aggregate_root.id GROUP BY aggregate_root.id HAVING COUNT(DISTINCT vacancy.user_id) <> 1',
            $table,
            $vacancyOwnerColumn,
        ));
        /** @var list<string> $ids */
        $ids = array_values(array_filter($ids, 'is_string'));
        if ([] !== $ids) {
            throw new \LogicException(sprintf('Cannot determine one user owner for %s: %s. Each record must be linked to vacancies owned by exactly one user before this migration can run.', $table, implode(', ', $ids)));
        }
    }
}
