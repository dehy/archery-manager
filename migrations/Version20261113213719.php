<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113213719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let each user opt in to the redesigned interface while it is being built';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD new_interface TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP new_interface');
    }
}
