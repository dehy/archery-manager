<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221016222439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE result CHANGE age_category age_category ENUM(\'U11\', \'U13\', \'U15\', \'U18\', \'U21\', \'S1\', \'S2\', \'S3\', \'P\', \'B\', \'M\', \'C\', \'J\', \'S\', \'V\', \'SV\') DEFAULT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE result CHANGE age_category age_category VARCHAR(255) DEFAULT NULL');
    }
}
