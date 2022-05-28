<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220528182919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE licensee (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, gender ENUM(\'M\', \'F\') NOT NULL COMMENT \'(DC2Type:GenderType)\', lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, birthdate DATE NOT NULL, ffta_member_code VARCHAR(7) DEFAULT NULL, ffta_id INT DEFAULT NULL, INDEX IDX_8BE6BA6EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO licensee (id, user_id, gender, lastname, firstname, birthdate) SELECT u.id, u.id, u.gender, u.lastname, u.firstname, u.birthdate FROM user u');
        $this->addSql('ALTER TABLE licensee ADD CONSTRAINT FK_8BE6BA6EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE arrow DROP FOREIGN KEY FK_D837EE3E7E3C61F9');
        $this->addSql('ALTER TABLE arrow ADD CONSTRAINT FK_D837EE3E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE bow DROP FOREIGN KEY FK_981655AB7E3C61F9');
        $this->addSql('ALTER TABLE bow ADD CONSTRAINT FK_981655AB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E39D1C3019');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E39D1C3019 FOREIGN KEY (participant_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE license DROP FOREIGN KEY FK_5768F4197E3C61F9');
        $this->addSql('DROP INDEX IDX_5768F4197E3C61F9 ON license');
        $this->addSql('ALTER TABLE license DROP number, CHANGE owner_id licensee_id INT NOT NULL');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F419734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id)');
        $this->addSql('CREATE INDEX IDX_5768F419734B22EE ON license (licensee_id)');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113A76ED395');
        $this->addSql('DROP INDEX IDX_136AC113A76ED395 ON result');
        $this->addSql('ALTER TABLE result CHANGE user_id licensee_id INT NOT NULL');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id)');
        $this->addSql('CREATE INDEX IDX_136AC113734B22EE ON result (licensee_id)');
        $this->addSql('ALTER TABLE user ADD phone_number VARCHAR(12) DEFAULT NULL, DROP birthdate, DROP is_verified');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE arrow DROP FOREIGN KEY FK_D837EE3E7E3C61F9');
        $this->addSql('ALTER TABLE bow DROP FOREIGN KEY FK_981655AB7E3C61F9');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E39D1C3019');
        $this->addSql('ALTER TABLE license DROP FOREIGN KEY FK_5768F419734B22EE');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113734B22EE');
        $this->addSql('ALTER TABLE arrow DROP FOREIGN KEY FK_D837EE3E7E3C61F9');
        $this->addSql('ALTER TABLE arrow ADD CONSTRAINT FK_D837EE3E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bow DROP FOREIGN KEY FK_981655AB7E3C61F9');
        $this->addSql('ALTER TABLE bow ADD CONSTRAINT FK_981655AB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE event_participation DROP FOREIGN KEY FK_8F0C52E39D1C3019');
        $this->addSql('ALTER TABLE event_participation ADD CONSTRAINT FK_8F0C52E39D1C3019 FOREIGN KEY (participant_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX IDX_5768F419734B22EE ON license');
        $this->addSql('ALTER TABLE license ADD number VARCHAR(7) DEFAULT NULL, CHANGE licensee_id owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F4197E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5768F4197E3C61F9 ON license (owner_id)');
        $this->addSql('DROP INDEX IDX_136AC113734B22EE ON result');
        $this->addSql('ALTER TABLE result CHANGE licensee_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_136AC113A76ED395 ON result (user_id)');
        $this->addSql('ALTER TABLE `user` ADD birthdate DATE NOT NULL, ADD is_verified TINYINT(1) NOT NULL, DROP phone_number');
        $this->addSql('UPDATE user u INNER JOIN licensee l.id = u.id SET u.gender = l.gender, u.lastname = l.lastname, u.firstname = l.firstname, u.birthdate = l.birthdate');
        $this->addSql('DROP TABLE licensee');
    }
}
