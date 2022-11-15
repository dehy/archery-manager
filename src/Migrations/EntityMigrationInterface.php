<?php

namespace App\Migrations;

use Doctrine\ORM\EntityManagerInterface;

interface EntityMigrationInterface
{
    public function setEntityManager(EntityManagerInterface $entityManager);
}
