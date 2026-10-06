<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006195353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_session table for mobile API token sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_session (id INT AUTO_INCREMENT NOT NULL, device_name VARCHAR(255) DEFAULT NULL, access_token_hash VARCHAR(64) NOT NULL, access_token_expires_at DATETIME NOT NULL, refresh_token_hash VARCHAR(64) NOT NULL, previous_refresh_token_hash VARCHAR(64) DEFAULT NULL, refresh_token_expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_3D7AA1B39982CF5B (access_token_hash), UNIQUE INDEX UNIQ_3D7AA1B37288F9EC (refresh_token_hash), INDEX IDX_3D7AA1B3A76ED395 (user_id), INDEX idx_api_session_previous_refresh (previous_refresh_token_hash), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE api_session ADD CONSTRAINT FK_3D7AA1B3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_session DROP FOREIGN KEY FK_3D7AA1B3A76ED395');
        $this->addSql('DROP TABLE api_session');
    }
}
