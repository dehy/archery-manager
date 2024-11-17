<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221106123723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation ADD participation_state ENUM(\'not_going\', \'going\', \'registered\') NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\'');
        $this->addSql('UPDATE event_participation SET participation_state = \'going\' WHERE present = TRUE');
        $this->addSql('ALTER TABLE event_participation DROP present');

        $this->addSql('CREATE TABLE event_group (event_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_2CDBF5E971F7E88B (event_id), INDEX IDX_2CDBF5E9FE54D947 (group_id), PRIMARY KEY(event_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_group ADD CONSTRAINT FK_2CDBF5E971F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_group ADD CONSTRAINT FK_2CDBF5E9FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO event_group (event_id, group_id) SELECT id, 1 FROM event WHERE name = \'Groupe 1\'');
        $this->addSql('INSERT INTO event_group (event_id, group_id) SELECT id, 1 FROM event WHERE type = \'contest_hobby\'');
        $this->addSql('INSERT INTO event_group (event_id, group_id) SELECT id, 2 FROM event WHERE name = \'Groupe 2\'');
        $this->addSql('INSERT INTO event_group (event_id, group_id) SELECT id, 2 FROM event WHERE type = \'contest_hobby\'');
        $this->addSql('INSERT INTO event_group (event_id, group_id) SELECT id, 3 FROM event WHERE name = \'Groupe 3\'');
        $this->addSql('INSERT INTO event_group (event_id, group_id) SELECT id, 3 FROM event WHERE type = \'contest_official\'');

        $this->addSql('ALTER TABLE result CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') NOT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation ADD present TINYINT(1) NOT NULL');
        $this->addSql('UPDATE event_participation SET present = FALSE WHERE participation_state = \'not_going\'');
        $this->addSql('UPDATE event_participation SET present = TRUE WHERE participation_state = \'going\' OR participation_state = \'registered\'');
        $this->addSql('ALTER TABLE event_participation DROP participation_state');

        $this->addSql('ALTER TABLE event_group DROP FOREIGN KEY FK_2CDBF5E971F7E88B');
        $this->addSql('ALTER TABLE event_group DROP FOREIGN KEY FK_2CDBF5E9FE54D947');
        $this->addSql('DROP TABLE event_group');

        $this->addSql('ALTER TABLE result CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') DEFAULT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\'');
    }
}
