<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let each user choose the theme (soft, colorful) and the mode (system, light, dark) of the redesigned interface';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `user` ADD interface_theme VARCHAR(16) DEFAULT 'soft' NOT NULL, ADD interface_mode VARCHAR(16) DEFAULT 'system' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP interface_theme, DROP interface_mode');
    }
}
