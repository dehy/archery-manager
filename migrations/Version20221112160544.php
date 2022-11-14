<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221112160544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation ADD departure INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_participation CHANGE `participation_state` `participation_state` enum(\'not_going\',\'going\',\'interested\',\'registered\') COLLATE \'utf8mb4_unicode_ci\' NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\' AFTER `result_id`');
        $this->addSql('UPDATE event_participation SET participation_state = \'interested\' WHERE participation_state = \'going\'');
        $this->addSql('ALTER TABLE event_participation CHANGE `participation_state` `participation_state` enum(\'not_going\',\'interested\',\'registered\') COLLATE \'utf8mb4_unicode_ci\' NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\' AFTER `result_id`');
        $this->addSql('ALTER TABLE user ADD discord_id VARCHAR(255) DEFAULT NULL, ADD discord_access_token LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation DROP departure');
        $this->addSql('ALTER TABLE event_participation CHANGE `participation_state` `participation_state` enum(\'not_going\',\'going\',\'interested\',\'registered\') COLLATE \'utf8mb4_unicode_ci\' NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\' AFTER `result_id`');
        $this->addSql('UPDATE event_participation SET participation_state = \'going\' WHERE participation_state = \'interested\'');
        $this->addSql('ALTER TABLE event_participation CHANGE `participation_state` `participation_state` enum(\'not_going\',\'going\',\'registered\') COLLATE \'utf8mb4_unicode_ci\' NOT NULL COMMENT \'(DC2Type:EventParticipationStateType)\' AFTER `result_id`');
        $this->addSql('ALTER TABLE user DROP discord_id, DROP discord_access_token');
    }
}
