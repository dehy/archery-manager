<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225073249 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE SET NULL + indexes on consent_log';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consent_log DROP FOREIGN KEY `FK_30113729A76ED395`');
        $this->addSql('ALTER TABLE consent_log CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE consent_log ADD CONSTRAINT FK_30113729A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consent_log DROP FOREIGN KEY FK_30113729A76ED395');
        $this->addSql('ALTER TABLE consent_log CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE consent_log ADD CONSTRAINT `FK_30113729A76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
