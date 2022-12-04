<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\TwigLevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        // SymfonyLevelSetList::UP_TO_SYMFONY_61,
        // TwigLevelSetList::UP_TO_TWIG_240,
        // PHPUnitLevelSetList::UP_TO_PHPUNIT_90,

        // SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        // DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        // DoctrineSetList::DOCTRINE_REPOSITORY_AS_SERVICE,
        // DoctrineSetList::DOCTRINE_ORM_213,
        // SensiolabsSetList::ANNOTATIONS_TO_ATTRIBUTES,
        // PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
};
