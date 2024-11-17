<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220612155615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applicant ADD season INT NOT NULL, ADD renewal TINYINT(1) NOT NULL, ADD license_type VARCHAR(32) DEFAULT NULL, CHANGE practice_level practice_level ENUM(\'beginner\', \'intermediate\', \'advanced\') DEFAULT NULL COMMENT \'(DC2Type:PracticeLevelType)\', CHANGE phone_number phone_number VARCHAR(12) DEFAULT NULL');
        $this->addSql('UPDATE applicant SET season = 2023 WHERE season = 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM applicant WHERE renewal = 1');
        $this->addSql('ALTER TABLE applicant DROP season, DROP renewal, DROP license_type, CHANGE practice_level practice_level ENUM(\'beginner\', \'intermediate\', \'advanced\') NOT NULL COMMENT \'(DC2Type:PracticeLevelType)\', CHANGE phone_number phone_number VARCHAR(12) NOT NULL');
    }
}
