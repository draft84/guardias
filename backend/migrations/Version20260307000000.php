<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create guard_levels table and link it to users table (MySQL compatible)';
    }

    public function up(Schema $schema): void
    {
        // Create guard_levels table with MySQL syntax
        $this->addSql('CREATE TABLE guard_levels (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_B0B0D8515E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add guard_level_id to users table
        $this->addSql('ALTER TABLE users ADD guard_level_id BINARY(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E98E298B30 FOREIGN KEY (guard_level_id) REFERENCES guard_levels (id)');
        $this->addSql('CREATE INDEX IDX_1483A5E98E298B30 ON users (guard_level_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key and column
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E98E298B30');
        $this->addSql('DROP INDEX IDX_1483A5E98E298B30 ON users');
        $this->addSql('ALTER TABLE users DROP COLUMN guard_level_id');

        // Drop guard_levels table
        $this->addSql('DROP TABLE guard_levels');
    }
}
