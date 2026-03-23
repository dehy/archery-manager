<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323210552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add calendar_token to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD calendar_token CHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6493363A255 ON `user` (calendar_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D6493363A255 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP calendar_token');
    }
}
