<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220928103455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE applicant ADD tournament TINYINT(1) NOT NULL AFTER license_type');
        $this->addSql('UPDATE applicant SET tournament = IF(license_type = "COMPÉTITION", 1, 0)');
        $this->addSql('ALTER TABLE applicant DROP license_type');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE applicant ADD license_type VARCHAR(32) DEFAULT NULL AFTER renewal');
        $this->addSql('UPDATE applicant SET license_type = IF(tournament = 1, "COMPÉTITION", "LOISIR")');
        $this->addSql('ALTER TABLE applicant DROP tournament');
    }
}
