<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220928192032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_attachment (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, type ENUM(\'mandate\', \'results\', \'misc\') NOT NULL COMMENT \'(DC2Type:EventAttachmentType)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', file_name VARCHAR(255) DEFAULT NULL, file_original_name VARCHAR(255) DEFAULT NULL, file_mime_type VARCHAR(255) DEFAULT NULL, file_size INT DEFAULT NULL, file_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', INDEX IDX_21B1009471F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE licensee_attachment (id INT AUTO_INCREMENT NOT NULL, licensee_id INT NOT NULL, season INT NOT NULL, type ENUM(\'license_application\', \'medical_certificate\', \'misc\') NOT NULL COMMENT \'(DC2Type:LicenseeAttachmentType)\', document_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', file_name VARCHAR(255) DEFAULT NULL, file_original_name VARCHAR(255) DEFAULT NULL, file_mime_type VARCHAR(255) DEFAULT NULL, file_size INT DEFAULT NULL, file_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', INDEX IDX_C61FEEE0734B22EE (licensee_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE practice_advice_attachment (id INT AUTO_INCREMENT NOT NULL, practice_advice_id INT NOT NULL, type ENUM(\'misc\') NOT NULL COMMENT \'(DC2Type:PracticeAdviceAttachmentType)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', file_name VARCHAR(255) DEFAULT NULL, file_original_name VARCHAR(255) DEFAULT NULL, file_mime_type VARCHAR(255) DEFAULT NULL, file_size INT DEFAULT NULL, file_dimensions LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', INDEX IDX_111F15AB62F41539 (practice_advice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_attachment ADD CONSTRAINT FK_21B1009471F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE licensee_attachment ADD CONSTRAINT FK_C61FEEE0734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE practice_advice_attachment ADD CONSTRAINT FK_111F15AB62F41539 FOREIGN KEY (practice_advice_id) REFERENCES practice_advice (id)');
        $this->addSql('ALTER TABLE event DROP mandate_filepath, DROP result_filepath');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_attachment DROP FOREIGN KEY FK_21B1009471F7E88B');
        $this->addSql('ALTER TABLE licensee_attachment DROP FOREIGN KEY FK_C61FEEE0734B22EE');
        $this->addSql('ALTER TABLE practice_advice_attachment DROP FOREIGN KEY FK_111F15AB62F41539');
        $this->addSql('DROP TABLE event_attachment');
        $this->addSql('DROP TABLE licensee_attachment');
        $this->addSql('DROP TABLE practice_advice_attachment');
        $this->addSql('ALTER TABLE event ADD mandate_filepath VARCHAR(255) DEFAULT NULL, ADD result_filepath VARCHAR(255) DEFAULT NULL');
    }
}
