<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323213821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add calendar_token to licensee table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licensee ADD calendar_token CHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8BE6BA6E3363A255 ON licensee (calendar_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8BE6BA6E3363A255 ON licensee');
        $this->addSql('ALTER TABLE licensee DROP calendar_token');
    }
}
