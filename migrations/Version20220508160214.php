<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220508160214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE result ADD user_id INT NOT NULL, ADD event_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC113A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE result ADD CONSTRAINT FK_136AC11371F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('CREATE INDEX IDX_136AC113A76ED395 ON result (user_id)');
        $this->addSql('CREATE INDEX IDX_136AC11371F7E88B ON result (event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP name');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC113A76ED395');
        $this->addSql('ALTER TABLE result DROP FOREIGN KEY FK_136AC11371F7E88B');
        $this->addSql('DROP INDEX IDX_136AC113A76ED395 ON result');
        $this->addSql('DROP INDEX IDX_136AC11371F7E88B ON result');
        $this->addSql('ALTER TABLE result DROP user_id, DROP event_id');
    }
}
