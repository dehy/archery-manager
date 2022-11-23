<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221121205241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licensee_attachment CHANGE type type ENUM(\'profile_picture\', \'license_application\', \'medical_certificate\', \'misc\') NOT NULL COMMENT \'(DC2Type:LicenseeAttachmentType)\', CHANGE season season INT DEFAULT NULL, CHANGE document_date document_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE licensee_attachment CHANGE type type ENUM(\'license_application\', \'medical_certificate\', \'misc\') NOT NULL COMMENT \'(DC2Type:LicenseeAttachmentType)\', CHANGE season season INT NOT NULL, CHANGE document_date document_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
