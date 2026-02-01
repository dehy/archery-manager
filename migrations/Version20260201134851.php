<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201134851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE license_application (id INT AUTO_INCREMENT NOT NULL, season INT NOT NULL, status ENUM(\'pending\', \'validated\', \'waiting_list\', \'rejected\') NOT NULL, rejection_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, licensee_id INT NOT NULL, club_id INT NOT NULL, processed_by_id INT DEFAULT NULL, INDEX IDX_864EC487734B22EE (licensee_id), INDEX IDX_864EC48761190A32 (club_id), INDEX IDX_864EC4872FFD4FD3 (processed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE license_application_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs JSON DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX type_5f948a911587b9c96a71f44de4f4a325_idx (type), INDEX object_id_5f948a911587b9c96a71f44de4f4a325_idx (object_id), INDEX discriminator_5f948a911587b9c96a71f44de4f4a325_idx (discriminator), INDEX transaction_hash_5f948a911587b9c96a71f44de4f4a325_idx (transaction_hash), INDEX blame_id_5f948a911587b9c96a71f44de4f4a325_idx (blame_id), INDEX created_at_5f948a911587b9c96a71f44de4f4a325_idx (created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE license_application ADD CONSTRAINT FK_864EC487734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE license_application ADD CONSTRAINT FK_864EC48761190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE license_application ADD CONSTRAINT FK_864EC4872FFD4FD3 FOREIGN KEY (processed_by_id) REFERENCES `user` (id)');
        $this->addSql('DROP TABLE dmishh_settings');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8EE38725E237E06 ON club (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dmishh_settings (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, value LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, owner_id VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE license_application DROP FOREIGN KEY FK_864EC487734B22EE');
        $this->addSql('ALTER TABLE license_application DROP FOREIGN KEY FK_864EC48761190A32');
        $this->addSql('ALTER TABLE license_application DROP FOREIGN KEY FK_864EC4872FFD4FD3');
        $this->addSql('DROP TABLE license_application');
        $this->addSql('DROP TABLE license_application_audit');
        $this->addSql('DROP INDEX UNIQ_B8EE38725E237E06 ON club');
    }
}
