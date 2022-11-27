<?php

namespace App\Migrations\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Psr\Log\LoggerInterface;

class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        return new $migrationClassName(
            $this->connection,
            $this->logger
        );
    }
}
