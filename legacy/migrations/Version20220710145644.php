<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220710145644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE result ADD age_category ENUM(\'P\', \'B\', \'M\', \'C\', \'J\', \'S1\', \'S2\', \'S3\', \'S\', \'V\', \'SV\') NOT NULL COMMENT \'(DC2Type:LicenseAgeCategoryType)\', ADD score1 INT DEFAULT NULL, ADD score2 INT DEFAULT NULL, ADD nb10 INT DEFAULT NULL, ADD nb10p INT DEFAULT NULL, ADD position INT DEFAULT NULL, CHANGE score total INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE result DROP age_category, DROP score1, DROP score2, DROP nb10, DROP nb10p, DROP position, CHANGE total score INT NOT NULL');
    }
}
