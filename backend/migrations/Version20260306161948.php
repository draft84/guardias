<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260306161948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY `FK_14CBC9906CA29A61`');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT FK_14CBC9906CA29A61 FOREIGN KEY (guard_id) REFERENCES guards (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY FK_14CBC9906CA29A61');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT `FK_14CBC9906CA29A61` FOREIGN KEY (guard_id) REFERENCES guards (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
