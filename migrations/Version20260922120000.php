<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add standalone companies, recruiters, and their direct contacts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE companies (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, website VARCHAR(2048) DEFAULT NULL, industry VARCHAR(255) DEFAULT NULL, INDEX IDX_COMPANIES_NAME (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recruiters (id INT AUTO_INCREMENT NOT NULL, agency_name VARCHAR(255) NOT NULL, website VARCHAR(2048) DEFAULT NULL, INDEX IDX_RECRUITERS_AGENCY_NAME (agency_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE direct_contacts (id INT AUTO_INCREMENT NOT NULL, company_id INT DEFAULT NULL, recruiter_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, linkedin_url VARCHAR(2048) DEFAULT NULL, INDEX IDX_DIRECT_CONTACT_COMPANY (company_id), INDEX IDX_DIRECT_CONTACT_RECRUITER (recruiter_id), INDEX IDX_DIRECT_CONTACT_NAME (name), CONSTRAINT FK_DIRECT_CONTACT_COMPANY FOREIGN KEY (company_id) REFERENCES companies (id) ON DELETE CASCADE, CONSTRAINT FK_DIRECT_CONTACT_RECRUITER FOREIGN KEY (recruiter_id) REFERENCES recruiters (id) ON DELETE CASCADE, CONSTRAINT direct_contact_has_one_owner CHECK ((company_id IS NOT NULL) <> (recruiter_id IS NOT NULL)), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE direct_contacts');
        $this->addSql('DROP TABLE recruiters');
        $this->addSql('DROP TABLE companies');
    }
}
