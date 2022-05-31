<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531122603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licensee CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8BE6BA6E68FC69F7 ON licensee (ffta_member_code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8BE6BA6EBD6F80AD ON licensee (ffta_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8BE6BA6E68FC69F7 ON licensee');
        $this->addSql('DROP INDEX UNIQ_8BE6BA6EBD6F80AD ON licensee');
        $this->addSql('ALTER TABLE licensee CHANGE user_id user_id INT NOT NULL');
    }
}
