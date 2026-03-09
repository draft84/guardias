<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304095148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to guard_assignments foreign key';
    }

    public function up(Schema $schema): void
    {
        // Eliminar la clave foránea existente y crearla con CASCADE
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY FK_14CBC9906CA29A61');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT FK_14CBC9906CA29A61 FOREIGN KEY (guard_id) REFERENCES guards(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Revertir a la clave foránea sin CASCADE
        $this->addSql('ALTER TABLE guard_assignments DROP FOREIGN KEY FK_14CBC9906CA29A61');
        $this->addSql('ALTER TABLE guard_assignments ADD CONSTRAINT FK_14CBC9906CA29A61 FOREIGN KEY (guard_id) REFERENCES guards(id)');
    }
}
