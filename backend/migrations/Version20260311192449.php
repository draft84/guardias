<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311192449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tasks (id BINARY(16) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, observations LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, due_date DATE NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, completion_notes LONGTEXT DEFAULT NULL, department_id BINARY(16) NOT NULL, created_by BINARY(16) NOT NULL, assigned_to BINARY(16) DEFAULT NULL, INDEX IDX_50586597AE80F5DF (department_id), INDEX IDX_50586597DE12AB56 (created_by), INDEX IDX_5058659789EEAF91 (assigned_to), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id)');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597DE12AB56 FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_5058659789EEAF91 FOREIGN KEY (assigned_to) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597AE80F5DF');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597DE12AB56');
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_5058659789EEAF91');
        $this->addSql('DROP TABLE tasks');
    }
}
