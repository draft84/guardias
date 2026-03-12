<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311202134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY `FK_5058659789EEAF91`');
        $this->addSql('DROP INDEX IDX_5058659789EEAF91 ON tasks');
        $this->addSql('ALTER TABLE tasks ADD shift_id BINARY(16) NOT NULL, DROP due_date, DROP completed_at, DROP completion_notes, DROP assigned_to');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597BB70BC0E FOREIGN KEY (shift_id) REFERENCES shifts (id)');
        $this->addSql('CREATE INDEX IDX_50586597BB70BC0E ON tasks (shift_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tasks DROP FOREIGN KEY FK_50586597BB70BC0E');
        $this->addSql('DROP INDEX IDX_50586597BB70BC0E ON tasks');
        $this->addSql('ALTER TABLE tasks ADD due_date DATE NOT NULL, ADD completed_at DATETIME DEFAULT NULL, ADD completion_notes LONGTEXT DEFAULT NULL, ADD assigned_to BINARY(16) DEFAULT NULL, DROP shift_id');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT `FK_5058659789EEAF91` FOREIGN KEY (assigned_to) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5058659789EEAF91 ON tasks (assigned_to)');
    }
}
