<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007135254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE applications (id INT AUTO_INCREMENT NOT NULL, job_offer_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT DEFAULT NULL, resume_url VARCHAR(500) DEFAULT NULL, status VARCHAR(255) NOT NULL, applied_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F7C966F03481D195 (job_offer_id), INDEX IDX_F7C966F0A76ED395 (user_id), UNIQUE INDEX unique_job_user (job_offer_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE companies (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, size VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE jobs_offers (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, location VARCHAR(255) DEFAULT NULL, contract_type VARCHAR(255) DEFAULT NULL, remote_type VARCHAR(255) NOT NULL, salary_min INT DEFAULT NULL, salary_max INT DEFAULT NULL, description LONGTEXT NOT NULL, requirements LONGTEXT DEFAULT NULL, applications_count INT DEFAULT 0 NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_17432326979B1AD6 (company_id), INDEX IDX_17432326A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) NOT NULL, city VARCHAR(255) DEFAULT NULL, zipcode VARCHAR(20) DEFAULT NULL, status VARCHAR(255) NOT NULL, roles JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F03481D195 FOREIGN KEY (job_offer_id) REFERENCES jobs_offers (id)');
        $this->addSql('ALTER TABLE applications ADD CONSTRAINT FK_F7C966F0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE jobs_offers ADD CONSTRAINT FK_17432326979B1AD6 FOREIGN KEY (company_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE jobs_offers ADD CONSTRAINT FK_17432326A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F03481D195');
        $this->addSql('ALTER TABLE applications DROP FOREIGN KEY FK_F7C966F0A76ED395');
        $this->addSql('ALTER TABLE jobs_offers DROP FOREIGN KEY FK_17432326979B1AD6');
        $this->addSql('ALTER TABLE jobs_offers DROP FOREIGN KEY FK_17432326A76ED395');
        $this->addSql('DROP TABLE applications');
        $this->addSql('DROP TABLE companies');
        $this->addSql('DROP TABLE jobs_offers');
        $this->addSql('DROP TABLE users');
    }
}
