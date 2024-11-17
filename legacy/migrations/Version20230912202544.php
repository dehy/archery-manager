<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230912202544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE licensee CHANGE ffta_member_code ffta_member_code VARCHAR(8) DEFAULT NULL');
        $this->addSql('UPDATE licensee SET ffta_member_code = CONCAT("0", ffta_member_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE licensee SET ffta_member_code = SUBSTR(ffta_member_code, 2)');
        $this->addSql('ALTER TABLE licensee CHANGE ffta_member_code ffta_member_code VARCHAR(7) DEFAULT NULL');
    }
}
