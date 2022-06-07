<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220607084911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD contest_type ENUM(\'federal\', \'international\', \'challenge33\') DEFAULT NULL COMMENT \'(DC2Type:ContestType)\', ADD discipline ENUM(\'target\', \'indoor\', \'field\', \'nature\', \'3d\', \'para\', \'run\') NOT NULL COMMENT \'(DC2Type:DisciplineType)\'');
        $this->addSql('ALTER TABLE license ADD activities LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE result ADD activity ENUM(\'AC\', \'AD\', \'BB\', \'CL\', \'CO\', \'TL\') NOT NULL COMMENT \'(DC2Type:LicenseActivityType)\', ADD target_size INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP contest_type, DROP discipline');
        $this->addSql('ALTER TABLE license DROP activities');
        $this->addSql('ALTER TABLE result DROP activity, DROP target_size');
    }
}
