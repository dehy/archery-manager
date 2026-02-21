<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221211314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename count->quantity on club_equipment (pool inventory), drop is_available, add purchase_price/purchase_date, add quantity on equipment_loan';
    }

    public function up(Schema $schema): void
    {
        // Set quantity=1 for any existing rows (count was nullable), then drop count and is_available
        $this->addSql('ALTER TABLE club_equipment ADD quantity INT NOT NULL DEFAULT 1, ADD purchase_price NUMERIC(10, 2) DEFAULT NULL, ADD purchase_date DATE DEFAULT NULL, DROP count, DROP is_available');
        // Existing loan rows represent 1-unit loans
        $this->addSql('ALTER TABLE equipment_loan ADD quantity INT NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_equipment ADD count INT DEFAULT NULL, ADD is_available TINYINT(1) NOT NULL DEFAULT 1, DROP quantity, DROP purchase_price, DROP purchase_date');
        $this->addSql('ALTER TABLE equipment_loan DROP quantity');
    }
}
