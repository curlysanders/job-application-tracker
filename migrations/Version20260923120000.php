<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the vacancy aggregate and its technology stack links.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vacancies (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, company_id INT DEFAULT NULL, recruiter_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, full_text LONGTEXT DEFAULT NULL, requirements LONGTEXT DEFAULT NULL, responsibilities LONGTEXT DEFAULT NULL, preferred_qualifications LONGTEXT DEFAULT NULL, about_job LONGTEXT DEFAULT NULL, about_company LONGTEXT DEFAULT NULL, compensation_benefits LONGTEXT DEFAULT NULL, source_urls JSON NOT NULL, how_to_apply LONGTEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, min_salary NUMERIC(12, 2) DEFAULT NULL, max_salary NUMERIC(12, 2) DEFAULT NULL, currency_code VARCHAR(3) NOT NULL, work_mode VARCHAR(20) DEFAULT NULL, hybrid_details LONGTEXT DEFAULT NULL, date_added DATETIME NOT NULL, date_posted DATETIME DEFAULT NULL, deadline DATETIME DEFAULT NULL, date_applied DATETIME DEFAULT NULL, next_action_at DATETIME DEFAULT NULL, next_action_title VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, archived TINYINT(1) NOT NULL, excitement INT DEFAULT NULL, contract_type VARCHAR(20) DEFAULT NULL, application_source VARCHAR(20) DEFAULT NULL, terminal_reason LONGTEXT DEFAULT NULL, scratchpad_notes LONGTEXT DEFAULT NULL, INDEX IDX_VACANCIES_STATUS (status), INDEX IDX_VACANCIES_USER (user_id), INDEX IDX_VACANCIES_DATE_ADDED (date_added), INDEX IDX_99165A59979B1AD6 (company_id), INDEX IDX_99165A59156BE243 (recruiter_id), CONSTRAINT FK_VACANCIES_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE, CONSTRAINT FK_VACANCIES_COMPANY FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE SET NULL, CONSTRAINT FK_VACANCIES_RECRUITER FOREIGN KEY (recruiter_id) REFERENCES recruiters (id) ON DELETE SET NULL, CONSTRAINT vacancy_salary_range CHECK ((min_salary IS NULL OR min_salary > 0) AND (max_salary IS NULL OR max_salary > 0) AND (min_salary IS NULL OR max_salary IS NULL OR min_salary <= max_salary)), CONSTRAINT vacancy_excitement_range CHECK (excitement IS NULL OR (excitement >= 0 AND excitement <= 5)), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vacancy_tech_stacks (vacancy_id INT NOT NULL, tech_stack_id INT NOT NULL, INDEX IDX_610876F3433B78C4 (vacancy_id), INDEX IDX_610876F331C0AE9B (tech_stack_id), PRIMARY KEY(vacancy_id, tech_stack_id), CONSTRAINT FK_610876F3433B78C4 FOREIGN KEY (vacancy_id) REFERENCES vacancies (id) ON DELETE CASCADE, CONSTRAINT FK_610876F331C0AE9B FOREIGN KEY (tech_stack_id) REFERENCES tech_stacks (id) ON DELETE CASCADE) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vacancy_tech_stacks');
        $this->addSql('DROP TABLE vacancies');
    }
}
