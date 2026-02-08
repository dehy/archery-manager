<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207171429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add birthdate to User table and populate from Licensee data';
    }

    public function up(Schema $schema): void
    {
        // Add birthdate column as nullable first
        $this->addSql('ALTER TABLE user ADD birthdate DATE DEFAULT NULL');

        // Fill birthdate from licensee table (use the first licensee's birthdate for each user)
        $this->addSql('
            UPDATE user u
            INNER JOIN licensee l ON l.user_id = u.id
            SET u.birthdate = l.birthdate
            WHERE u.birthdate IS NULL
            AND l.birthdate IS NOT NULL
            ORDER BY l.id
        ');

        // For users without licensees, set a default birthdate (1990-01-01)
        // This should not happen in production, but ensures data integrity
        $this->addSql('UPDATE user SET birthdate = "1990-01-01" WHERE birthdate IS NULL');

        // Now make the column NOT NULL
        $this->addSql('ALTER TABLE user MODIFY birthdate DATE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP birthdate');
    }
}
