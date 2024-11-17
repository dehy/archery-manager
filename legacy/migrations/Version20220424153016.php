<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220424153016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE arrow (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') NOT NULL COMMENT \'(DC2Type:ArrowType)\', spine INT NOT NULL, fletching ENUM(\'plastic\', \'spinwings\') NOT NULL COMMENT \'(DC2Type:FletchingType)\', INDEX IDX_D837EE3E7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bow (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') NOT NULL COMMENT \'(DC2Type:BowType)\', brand VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, limb_size INT DEFAULT NULL, limb_strength INT DEFAULT NULL, brace_height DOUBLE PRECISION DEFAULT NULL, draw_length INT DEFAULT NULL, INDEX IDX_981655AB7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, starts_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ends_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', address VARCHAR(255) NOT NULL, type ENUM(\'training\', \'contest_official\', \'contest_hobby\', \'other\') NOT NULL COMMENT \'(DC2Type:EventType)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_participation (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, participant_id INT NOT NULL, result_id INT DEFAULT NULL, present TINYINT(1) NOT NULL, INDEX IDX_8F0C52E371F7E88B (event_id), INDEX IDX_8F0C52E39D1C3019 (participant_id), UNIQUE INDEX UNIQ_8F0C52E37A7B643 (result_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE license (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, season INT NOT NULL, type ENUM(\'P\', \'J\', \'A\', \'L\', \'E\', \'S\', \'U\', \'H\', \'D\') NOT NULL COMMENT \'(DC2Type:LicenseType)\', number VARCHAR(7) DEFAULT NULL, category ENUM(\'P\', \'J\', \'A\') DEFAULT NULL COMMENT \'(DC2Type:LicenseCategoryType)\', age_category ENUM(\'P\', \'B\', \'M\', \'C\', \'J\', \'S1\', \'S2\', \'S3\') DEFAULT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\', INDEX IDX_5768F4197E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE result (id INT AUTO_INCREMENT NOT NULL, discipline ENUM(\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\') NOT NULL COMMENT \'(DC2Type:DisciplineType)\', distance INT DEFAULT NULL, score INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sight_adjustment (id INT AUTO_INCREMENT NOT NULL, bow_id INT NOT NULL, distance INT NOT NULL, setting VARCHAR(255) NOT NULL, INDEX IDX_35C0B1E180746B69 (bow_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, gender ENUM(\'M\', \'F\') NOT NULL COMMENT \'(DC2Type:GenderType)\', lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, birthdate DATE NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE arrow ADD CONSTRAINT FK_D837EE3E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bow ADD CONSTRAINT FK_981655AB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E371F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E39D1C3019 FOREIGN KEY (participant_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E37A7B643 FOREIGN KEY (result_id) REFERENCES result (id)');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F4197E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE sight_adjustment ADD CONSTRAINT FK_35C0B1E180746B69 FOREIGN KEY (bow_id) REFERENCES bow (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sight_adjustment DROP FOREIGN KEY FK_35C0B1E180746B69');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E371F7E88B');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E37A7B643');
        $this->addSql('ALTER TABLE arrow DROP FOREIGN KEY FK_D837EE3E7E3C61F9');
        $this->addSql('ALTER TABLE bow DROP FOREIGN KEY FK_981655AB7E3C61F9');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E39D1C3019');
        $this->addSql('ALTER TABLE license DROP FOREIGN KEY FK_5768F4197E3C61F9');
        $this->addSql('DROP TABLE arrow');
        $this->addSql('DROP TABLE bow');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_participation');
        $this->addSql('DROP TABLE license');
        $this->addSql('DROP TABLE result');
        $this->addSql('DROP TABLE sight_adjustment');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
