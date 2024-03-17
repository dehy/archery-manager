<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240211193137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_instance_exception (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, created_by_id INT NOT NULL, is_resecheduled TINYINT(1) NOT NULL, is_cancelled TINYINT(1) NOT NULL, start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', start_time TIME DEFAULT NULL COMMENT \'(DC2Type:time_immutable)\', end_time TIME DEFAULT NULL COMMENT \'(DC2Type:time_immutable)\', is_full_day_event TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_40EBBBED71F7E88B (event_id), INDEX IDX_40EBBBEDB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_recurring_pattern (event_id INT NOT NULL, recurring_type ENUM(\'daily\', \'weekly\', \'monthly\', \'yearly\') NOT NULL COMMENT \'(DC2Type:RecurringType)\', separation_count INT NOT NULL, max_num_of_occurrences INT DEFAULT NULL, day_of_week INT DEFAULT NULL, week_of_month INT DEFAULT NULL, day_of_month INT DEFAULT NULL, month_of_year INT DEFAULT NULL, PRIMARY KEY(event_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_instance_exception ADD CONSTRAINT FK_40EBBBED71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_instance_exception ADD CONSTRAINT FK_40EBBBEDB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event_recurring_pattern ADD CONSTRAINT FK_EED3336671F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event ADD description LONGTEXT DEFAULT NULL, ADD start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', ADD end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', ADD start_time TIME DEFAULT NULL COMMENT \'(DC2Type:time_immutable)\', ADD end_time TIME DEFAULT NULL COMMENT \'(DC2Type:time_immutable)\', ADD recurring TINYINT(1) NOT NULL, ADD created_by_id INT NOT NULL, ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD parent_event_id INT DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE all_day full_day_event TINYINT(1) NOT NULL');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7B03A8386 ON event (created_by_id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7EE3A445A ON event (parent_event_id)');
        $this->addSql('UPDATE event SET start_date = CONCAT(DATE(starts_at), " 00:00:00"), end_date = CONCAT(DATE(ends_at), " 00:00:00"), start_time = starts_at, end_time = ends_at, created_by_id = 1, created_at = NOW()');
        $this->addSql('ALTER TABLE event DROP starts_at, DROP ends_at');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7EE3A445A FOREIGN KEY (parent_event_id) REFERENCES event (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_instance_exception DROP FOREIGN KEY FK_40EBBBED71F7E88B');
        $this->addSql('ALTER TABLE event_instance_exception DROP FOREIGN KEY FK_40EBBBEDB03A8386');
        $this->addSql('ALTER TABLE event_recurring_pattern DROP FOREIGN KEY FK_EED3336671F7E88B');
        $this->addSql('DROP TABLE event_instance_exception');
        $this->addSql('DROP TABLE event_recurring_pattern');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7B03A8386');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7EE3A445A');
        $this->addSql('ALTER TABLE event ADD starts_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',ADD ends_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE full_day_event all_day TINYINT(1) NOT NULL, DROP description, DROP recurring, DROP created_by_id, DROP parent_event_id, CHANGE address address VARCHAR(255) NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP created_at');
        $this->addSql('UPDATE event SET starts_at = CONCAT(DATE(start_date), " ", TIME(start_time)), ends_at = CONCAT(DATE(end_date), " ", TIME(end_time))');
        $this->addSql('ALTER TABLE event DROP start_date, DROP end_date, DROP start_time, DROP end_time');
    }
}
