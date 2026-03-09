<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304094130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add weekDays column to guards table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guards ADD week_days JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guards DROP week_days');
    }
}
