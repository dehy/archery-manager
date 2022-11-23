<?php

namespace App\Migrations\Factory;

use App\Migrations\EntityMigrationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        /** @var AbstractMigration $migration */
        return new $migrationClassName(
            $this->connection,
            $this->logger
        );

        /*if ($migration instanceof EntityMigrationInterface) {
            $migration->setEntityManager($this->entityManager);
        }*/
    }
}
