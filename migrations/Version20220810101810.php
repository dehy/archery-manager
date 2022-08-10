<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220810101810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE practice_advice (id INT AUTO_INCREMENT NOT NULL, licensee_id INT NOT NULL, author_id INT NOT NULL, title VARCHAR(255) NOT NULL, advice LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', archived_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C8163885734B22EE (licensee_id), INDEX IDX_C8163885F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE practice_advice ADD CONSTRAINT FK_C8163885734B22EE FOREIGN KEY (licensee_id) REFERENCES licensee (id)');
        $this->addSql('ALTER TABLE practice_advice ADD CONSTRAINT FK_C8163885F675F31B FOREIGN KEY (author_id) REFERENCES licensee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE practice_advice');
    }
}
