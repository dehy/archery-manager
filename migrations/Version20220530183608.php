<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220530183608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE group_licensee (group_id INT NOT NULL, licensee_id INT NOT NULL, INDEX IDX_82D6F611FE54D947 (group_id), INDEX IDX_82D6F611734B22EE (licensee_id), PRIMARY KEY(group_id, licensee_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE group_licensee ADD CONSTRAINT FK_82D6F611FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_licensee ADD CONSTRAINT FK_82D6F611734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_licensee DROP FOREIGN KEY FK_82D6F611FE54D947');
        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE group_licensee');
    }
}
