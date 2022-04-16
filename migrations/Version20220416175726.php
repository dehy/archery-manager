<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220416175726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE arrow_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE bow_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE event_participation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE license_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE result_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sight_adjustment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE arrow (id INT NOT NULL, owner_id INT NOT NULL, type VARCHAR(255) CHECK(type IN (\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\')) NOT NULL, spine INT NOT NULL, fletching VARCHAR(255) CHECK(fletching IN (\'plastic\', \'spinwings\')) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D837EE3E7E3C61F9 ON arrow (owner_id)');
        $this->addSql('COMMENT ON COLUMN arrow.type IS \'(DC2Type:ArrowType)\'');
        $this->addSql('COMMENT ON COLUMN arrow.fletching IS \'(DC2Type:FletchingType)\'');
        $this->addSql('CREATE TABLE bow (id INT NOT NULL, owner_id INT NOT NULL, type VARCHAR(255) CHECK(type IN (\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\')) NOT NULL, brand VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, limb_size INT DEFAULT NULL, limb_strength INT DEFAULT NULL, brace_height DOUBLE PRECISION DEFAULT NULL, draw_length INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_981655AB7E3C61F9 ON bow (owner_id)');
        $this->addSql('COMMENT ON COLUMN bow.type IS \'(DC2Type:BowType)\'');
        $this->addSql('CREATE TABLE event (id INT NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ends_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, address VARCHAR(255) NOT NULL, type VARCHAR(255) CHECK(type IN (\'training\', \'contest_official\', \'contest_hobby\', \'other\')) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN event.starts_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN event.ends_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN event.type IS \'(DC2Type:EventType)\'');
        $this->addSql('CREATE TABLE event_participation (id INT NOT NULL, event_id INT NOT NULL, participant_id INT NOT NULL, result_id INT DEFAULT NULL, present BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F0C52E371F7E88B ON event_participation (event_id)');
        $this->addSql('CREATE INDEX IDX_8F0C52E39D1C3019 ON event_participation (participant_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8F0C52E37A7B643 ON event_participation (result_id)');
        $this->addSql('CREATE TABLE license (id INT NOT NULL, owner_id INT NOT NULL, season INT NOT NULL, type VARCHAR(255) CHECK(type IN (\'P\', \'J\', \'A\', \'L\', \'E\', \'S\', \'U\', \'H\', \'D\')) NOT NULL, number VARCHAR(7) DEFAULT NULL, category VARCHAR(255) CHECK(category IN (\'P\', \'J\', \'A\')) DEFAULT NULL, age_category VARCHAR(255) CHECK(age_category IN (\'P\', \'B\', \'M\', \'C\', \'J\', \'S1\', \'S2\', \'S3\')) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5768F4197E3C61F9 ON license (owner_id)');
        $this->addSql('COMMENT ON COLUMN license.type IS \'(DC2Type:LicenseType)\'');
        $this->addSql('COMMENT ON COLUMN license.category IS \'(DC2Type:LicenseCategoryType)\'');
        $this->addSql('COMMENT ON COLUMN license.age_category IS \'(DC2Type:LicenseAgeCategoryType)\'');
        $this->addSql('CREATE TABLE result (id INT NOT NULL, discipline VARCHAR(255) CHECK(discipline IN (\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\')) NOT NULL, distance INT DEFAULT NULL, score INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN result.discipline IS \'(DC2Type:DisciplineType)\'');
        $this->addSql('CREATE TABLE sight_adjustment (id INT NOT NULL, bow_id INT NOT NULL, distance INT NOT NULL, setting VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_35C0B1E180746B69 ON sight_adjustment (bow_id)');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, gender VARCHAR(255) CHECK(gender IN (\'M\', \'F\')) NOT NULL, lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, birthdate DATE NOT NULL, is_verified BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".gender IS \'(DC2Type:GenderType)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE arrow ADD CONSTRAINT FK_D837EE3E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bow ADD CONSTRAINT FK_981655AB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E371F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E39D1C3019 FOREIGN KEY (participant_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E37A7B643 FOREIGN KEY (result_id) REFERENCES result (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F4197E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sight_adjustment ADD CONSTRAINT FK_35C0B1E180746B69 FOREIGN KEY (bow_id) REFERENCES bow (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE sight_adjustment DROP CONSTRAINT FK_35C0B1E180746B69');
        $this->addSql('ALTER TABLE event_participation DROP CONSTRAINT FK_8F0C52E371F7E88B');
        $this->addSql('ALTER TABLE event_participation DROP CONSTRAINT FK_8F0C52E37A7B643');
        $this->addSql('ALTER TABLE arrow DROP CONSTRAINT FK_D837EE3E7E3C61F9');
        $this->addSql('ALTER TABLE bow DROP CONSTRAINT FK_981655AB7E3C61F9');
        $this->addSql('ALTER TABLE event_participation DROP CONSTRAINT FK_8F0C52E39D1C3019');
        $this->addSql('ALTER TABLE license DROP CONSTRAINT FK_5768F4197E3C61F9');
        $this->addSql('DROP SEQUENCE arrow_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE bow_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE event_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE event_participation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE license_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE result_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE sight_adjustment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP TABLE arrow');
        $this->addSql('DROP TABLE bow');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_participation');
        $this->addSql('DROP TABLE license');
        $this->addSql('DROP TABLE result');
        $this->addSql('DROP TABLE sight_adjustment');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
