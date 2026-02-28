<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260228142418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event scope/visibility (incl. international), FFTA public event fields, and club geographic fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club ADD department_code VARCHAR(3) DEFAULT NULL, ADD region_code VARCHAR(10) DEFAULT NULL, ADD watched_department_codes JSON NOT NULL, ADD watched_region_codes JSON NOT NULL');
        $this->addSql('DROP INDEX consent_log_created_at_idx ON consent_log');
        $this->addSql('DROP INDEX consent_log_action_idx ON consent_log');
        $this->addSql('ALTER TABLE consent_log CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE event ADD scope ENUM(\'club\', \'departmental\', \'regional\', \'national\', \'international\') DEFAULT \'club\' NOT NULL, ADD ffta_event_id INT DEFAULT NULL, ADD ffta_comite_departemental VARCHAR(255) DEFAULT NULL, ADD ffta_comite_regional VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7AFED7D93 ON event (ffta_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club DROP department_code, DROP region_code, DROP watched_department_codes, DROP watched_region_codes');
        $this->addSql('ALTER TABLE consent_log CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX consent_log_created_at_idx ON consent_log (created_at)');
        $this->addSql('CREATE INDEX consent_log_action_idx ON consent_log (action)');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA7AFED7D93 ON event');
        $this->addSql('ALTER TABLE event DROP scope, DROP ffta_event_id, DROP ffta_comite_departemental, DROP ffta_comite_regional');
    }
}
