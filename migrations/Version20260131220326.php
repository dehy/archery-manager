<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260131220326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_equipment (id INT AUTO_INCREMENT NOT NULL, club_id INT NOT NULL, type ENUM(\'bow\', \'arrows\', \'quiver\', \'armguard\', \'finger_tab\', \'chest_guard\', \'other\') NOT NULL COMMENT \'(DC2Type:ClubEquipmentType)\', name VARCHAR(255) NOT NULL, serial_number VARCHAR(255) DEFAULT NULL, count INT DEFAULT NULL, bow_type ENUM(\'initiation\', \'classique_competition\', \'poulies\', \'barebow\', \'longbow\') DEFAULT NULL COMMENT \'(DC2Type:BowType)\', brand VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, limb_size INT DEFAULT NULL, limb_strength INT DEFAULT NULL, arrow_type ENUM(\'wood\', \'aluminum\', \'carbon\', \'aluminum_carbon\') DEFAULT NULL COMMENT \'(DC2Type:ArrowType)\', arrow_length INT DEFAULT NULL, arrow_spine VARCHAR(50) DEFAULT NULL, fletching_type ENUM(\'plastic\', \'spinwings\') DEFAULT NULL COMMENT \'(DC2Type:FletchingType)\', notes LONGTEXT DEFAULT NULL, is_available TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7C31756561190A32 (club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipment_loan (id INT AUTO_INCREMENT NOT NULL, equipment_id INT NOT NULL, borrower_id INT NOT NULL, created_by_id INT DEFAULT NULL, start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', return_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FF57DC05517FE9FE (equipment_id), INDEX IDX_FF57DC0511CE312B (borrower_id), INDEX IDX_FF57DC05B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE club_equipment ADD CONSTRAINT FK_7C31756561190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE equipment_loan ADD CONSTRAINT FK_FF57DC05517FE9FE FOREIGN KEY (equipment_id) REFERENCES club_equipment (id)');
        $this->addSql('ALTER TABLE equipment_loan ADD CONSTRAINT FK_FF57DC0511CE312B FOREIGN KEY (borrower_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE equipment_loan ADD CONSTRAINT FK_FF57DC05B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_equipment DROP FOREIGN KEY FK_7C31756561190A32');
        $this->addSql('ALTER TABLE equipment_loan DROP FOREIGN KEY FK_FF57DC05517FE9FE');
        $this->addSql('ALTER TABLE equipment_loan DROP FOREIGN KEY FK_FF57DC0511CE312B');
        $this->addSql('ALTER TABLE equipment_loan DROP FOREIGN KEY FK_FF57DC05B03A8386');
        $this->addSql('DROP TABLE club_equipment');
        $this->addSql('DROP TABLE equipment_loan');
    }
}
