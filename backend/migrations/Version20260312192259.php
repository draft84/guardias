<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312192259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task_logs (id BINARY(16) NOT NULL, date DATE NOT NULL, start_time TIME DEFAULT NULL, verification_time TIME DEFAULT NULL, observations LONGTEXT DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, task_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, INDEX IDX_833C52278DB60186 (task_id), INDEX IDX_833C5227A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE task_logs ADD CONSTRAINT FK_833C52278DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id)');
        $this->addSql('ALTER TABLE task_logs ADD CONSTRAINT FK_833C5227A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tasks ADD is_daily TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_logs DROP FOREIGN KEY FK_833C52278DB60186');
        $this->addSql('ALTER TABLE task_logs DROP FOREIGN KEY FK_833C5227A76ED395');
        $this->addSql('DROP TABLE task_logs');
        $this->addSql('ALTER TABLE tasks DROP is_daily');
    }
}
