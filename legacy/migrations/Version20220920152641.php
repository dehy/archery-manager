<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220920152641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applicant ADD docs_retrieved TINYINT(1) NOT NULL, ADD paid TINYINT(1) NOT NULL, ADD license_created TINYINT(1) NOT NULL, ADD payment_observations VARCHAR(255) DEFAULT NULL, CHANGE on_waiting_list on_waiting_list TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE applicant DROP docs_retrieved, DROP paid, DROP license_created, DROP payment_observations, CHANGE on_waiting_list on_waiting_list TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
