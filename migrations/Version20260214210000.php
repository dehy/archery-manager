<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add security features: User lockout fields and SecurityLog table.
 */
final class Version20260214210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add brute-force protection: User lockout fields and SecurityLog audit table';
    }

    public function up(Schema $schema): void
    {
        // Add user lockout tracking fields
        $this->addSql('ALTER TABLE user ADD failed_login_attempts INT DEFAULT 0 NOT NULL, ADD last_failed_login_at DATETIME DEFAULT NULL, ADD account_locked_until DATETIME DEFAULT NULL, ADD suspicious_activity_notified_at DATETIME DEFAULT NULL');

        // Create security log table for audit trail
        $this->addSql('CREATE TABLE security_log (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, ip_address VARCHAR(45) NOT NULL, event_type VARCHAR(50) NOT NULL, user_agent LONGTEXT DEFAULT NULL, details LONGTEXT DEFAULT NULL, occurred_at DATETIME NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_FE5C6A69A76ED395 (user_id), INDEX idx_occurred_at (occurred_at), INDEX idx_ip_address (ip_address), INDEX idx_event_type (event_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE security_log ADD CONSTRAINT FK_FE5C6A69A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove security log table
        $this->addSql('ALTER TABLE security_log DROP FOREIGN KEY FK_FE5C6A69A76ED395');
        $this->addSql('DROP TABLE security_log');

        // Remove user lockout fields
        $this->addSql('ALTER TABLE `user` DROP failed_login_attempts, DROP last_failed_login_at, DROP account_locked_until, DROP suspicious_activity_notified_at');
    }
}
