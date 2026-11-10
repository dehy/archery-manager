<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110220351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Flag profile pictures uploaded by hand, which the FFTA sync keeps';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE licensee_attachment ADD manual_upload TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE licensee_attachment DROP manual_upload');
    }
}
