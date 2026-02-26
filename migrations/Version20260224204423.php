<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224204423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add consent_log table for GDPR consent audit trail';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consent_log (id INT AUTO_INCREMENT NOT NULL, services JSON NOT NULL, action VARCHAR(16) NOT NULL, policy_version VARCHAR(32) NOT NULL, ip_address_anonymized VARCHAR(64) NOT NULL, user_agent LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_id INT DEFAULT NULL, INDEX IDX_30113729A76ED395 (user_id), INDEX consent_log_created_at_idx (created_at), INDEX consent_log_action_idx (action), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE consent_log ADD CONSTRAINT FK_30113729A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consent_log DROP FOREIGN KEY FK_30113729A76ED395');
        $this->addSql('DROP TABLE consent_log');
    }
}
