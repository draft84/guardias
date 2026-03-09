<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309134345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guard_levels RENAME INDEX uniq_b0b0d8515e237e06 TO UNIQ_E7CECBE15E237E06');
        $this->addSql('ALTER TABLE guards DROP level');
        $this->addSql('ALTER TABLE users CHANGE phone phone VARCHAR(35) DEFAULT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX idx_1483a5e98e298b30 TO IDX_1483A5E9C5BDE68F');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guard_levels RENAME INDEX uniq_e7cecbe15e237e06 TO UNIQ_B0B0D8515E237E06');
        $this->addSql('ALTER TABLE guards ADD level VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE phone phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE users RENAME INDEX idx_1483a5e9c5bde68f TO IDX_1483A5E98E298B30');
    }
}
