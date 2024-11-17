<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230211100923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, city VARCHAR(255), logo_name VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) NOT NULL, contact_email VARCHAR(255) NOT NULL, ffta_code VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO `club` (`name`, `city`, `logo_name`, `primary_color`, `contact_email`, `ffta_code`, `created_at`, `updated_at`) VALUES (\'Les Archers de Guyenne\', \'Bordeaux\', \'logo.svg\', \'#e31d02\', \'lesarchersdeguyenne@gmail.com\', \'1033093\', now(), now())');
        $this->addSql('ALTER TABLE event ADD club_id INT DEFAULT NULL');
        $this->addSql('UPDATE event SET club_id = 1');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA761190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA761190A32 ON event (club_id)');
        $this->addSql('ALTER TABLE license ADD club_id INT NOT NULL');
        $this->addSql('UPDATE license SET club_id = 1');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F41961190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('CREATE INDEX IDX_5768F41961190A32 ON license (club_id)');
        $this->addSql('ALTER TABLE `group` ADD club_id INT NOT NULL');
        $this->addSql('UPDATE `group` SET club_id = 1');
        $this->addSql('ALTER TABLE `group` ADD CONSTRAINT FK_6DC044C561190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('CREATE INDEX IDX_6DC044C561190A32 ON `group` (club_id)');
        $this->addSql('ALTER TABLE event_participation CHANGE participation_state participation_state ENUM(\'not_going\', \'interested\', \'registered\') NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\'');
        $this->addSql('ALTER TABLE user CHANGE discord_access_token discord_access_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license DROP FOREIGN KEY FK_5768F41961190A32');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA761190A32');
        $this->addSql('DROP INDEX IDX_5768F41961190A32 ON license');
        $this->addSql('DROP INDEX IDX_3BAE0AA761190A32 ON event');
        $this->addSql('ALTER TABLE license DROP club_id');
        $this->addSql('ALTER TABLE event DROP club_id');
        $this->addSql('DROP TABLE club');
        $this->addSql('ALTER TABLE event_participation CHANGE participation_state participation_state ENUM(\'not_going\', \'interested\', \'registered\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:EventParticipationStateType)\'');
    }
}
