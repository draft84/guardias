<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304114138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add valid_from and valid_until columns to guards table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guards ADD valid_from DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE guards ADD valid_until DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guards DROP valid_from');
        $this->addSql('ALTER TABLE guards DROP valid_until');
    }
}
