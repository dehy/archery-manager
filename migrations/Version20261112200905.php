<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112200905 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the club FFTA credentials, only used by the removed FFTA website sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club DROP ffta_username, DROP ffta_password');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club ADD ffta_username VARCHAR(255) DEFAULT NULL, ADD ffta_password VARCHAR(255) DEFAULT NULL');
    }
}
