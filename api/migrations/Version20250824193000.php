<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add email verification fields to users table
 */
final class Version20250824193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification token and expiration fields to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD email_verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD email_verification_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN users.email_verification_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP email_verification_token');
        $this->addSql('ALTER TABLE users DROP email_verification_token_expires_at');
    }
}
