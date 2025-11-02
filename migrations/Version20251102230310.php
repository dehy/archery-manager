<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102230310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make target_type nullable in event_participation table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') DEFAULT NULL COMMENT \'(DC2Type:TargetTypeType)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_participation CHANGE target_type target_type ENUM(\'monospot\', \'trispot\', \'field\', \'animal\', \'beursault\') NOT NULL COMMENT \'(DC2Type:TargetTypeType)\'');
    }
}
