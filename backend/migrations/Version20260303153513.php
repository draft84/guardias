<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303153513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE departments (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, parent_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_16AEB8D45E237E06 (name), UNIQUE INDEX UNIQ_16AEB8D477153098 (code), INDEX IDX_16AEB8D4727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guard_assignments (id BINARY(16) NOT NULL, date DATE NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, status VARCHAR(20) DEFAULT \'scheduled\' NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, swapped_at DATETIME DEFAULT NULL, guard_id BINARY(16) NOT NULL, user_id BINARY(16) NOT NULL, assigned_by BINARY(16) DEFAULT NULL, INDEX IDX_14CBC9906CA29A61 (guard_id), INDEX IDX_14CBC990A76ED395 (user_id), INDEX IDX_14CBC99061A2AF17 (assigned_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guards (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, department_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_8973538377153098 (code), INDEX IDX_89735383AE80F5DF (department_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shift_swap_requests (id BINARY(16) NOT NULL, requested_at DATETIME NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, approved_at DATETIME DEFAULT NULL, reason LONGTEXT DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, original_assignment_id BINARY(16) NOT NULL, new_user_id BINARY(16) NOT NULL, requested_by BINARY(16) NOT NULL, approved_by BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_F7854C761EF2DBA7 (original_assignment_id), INDEX IDX_F7854C767C2D807B (new_user_id), INDEX IDX_F7854C7618C491A5 (requested_by), INDEX IDX_F7854C764EA3CB3D (approved_by), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shifts (id BINARY(16) NOT NULL, name VARCHAR(100) NOT NULL, code VARCHAR(50) NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, type VARCHAR(20) NOT NULL, color VARCHAR(7) DEFAULT \'#3498db\' NOT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1D1D712F77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id BINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, roles JSON NOT NULL, active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_login DATETIME DEFAULT NULL, department_id BINARY(16) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), INDEX IDX_1483A5E9AE80F5DF (department_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE departments ADD CONSTRAINT FK_16AEB8D4727ACA70 FOREIGN KEY (parent_id) REFERENCES departments (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT FK_14CBC9906CA29A61 FOREIGN KEY (guard_id) REFERENCES guards (id)');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT FK_14CBC990A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT FK_14CBC99061A2AF17 FOREIGN KEY (assigned_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE guards ADD CONSTRAINT FK_89735383AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id)');
        $this->addSql('ALTER TABLE shift_swap_requests ADD CONSTRAINT FK_F7854C761EF2DBA7 FOREIGN KEY (original_assignment_id) REFERENCES guard_assignments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shift_swap_requests ADD CONSTRAINT FK_F7854C767C2D807B FOREIGN KEY (new_user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE shift_swap_requests ADD CONSTRAINT FK_F7854C7618C491A5 FOREIGN KEY (requested_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE shift_swap_requests ADD CONSTRAINT FK_F7854C764EA3CB3D FOREIGN KEY (approved_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E9AE80F5DF FOREIGN KEY (department_id) REFERENCES departments (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departments DROP FOREIGN KEY FK_16AEB8D4727ACA70');
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY FK_14CBC9906CA29A61');
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY FK_14CBC990A76ED395');
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY FK_14CBC99061A2AF17');
        $this->addSql('ALTER TABLE guards DROP FOREIGN KEY FK_89735383AE80F5DF');
        $this->addSql('ALTER TABLE shift_swap_requests DROP FOREIGN KEY FK_F7854C761EF2DBA7');
        $this->addSql('ALTER TABLE shift_swap_requests DROP FOREIGN KEY FK_F7854C767C2D807B');
        $this->addSql('ALTER TABLE shift_swap_requests DROP FOREIGN KEY FK_F7854C7618C491A5');
        $this->addSql('ALTER TABLE shift_swap_requests DROP FOREIGN KEY FK_F7854C764EA3CB3D');
        $this->addSql('ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9AE80F5DF');
        $this->addSql('DROP TABLE departments');
        $this->addSql('DROP TABLE guard_assignments');
        $this->addSql('DROP TABLE guards');
        $this->addSql('DROP TABLE shift_swap_requests');
        $this->addSql('DROP TABLE shifts');
        $this->addSql('DROP TABLE users');
    }
}
