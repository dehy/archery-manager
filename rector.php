<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Equal\UseIdenticalOverEqualWithSameTypeRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\ClassMethod\BareCreateMockAssignToDirectUseRector;
use Rector\Symfony\CodeQuality\Rector\Class_\ControllerMethodInjectionToConstructorRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        UseIdenticalOverEqualWithSameTypeRector::class => [
            __DIR__.'/src/Helper/ObjectComparator.php',
        ],
        ControllerMethodInjectionToConstructorRector::class => [
            __DIR__.'/src/Controller/Admin/*',
        ],
        BareCreateMockAssignToDirectUseRector::class => [
            __DIR__.'/tests/Unit/Entity/ClubTest.php',
            __DIR__.'/tests/Unit/Entity/EventTest.php',
            __DIR__.'/tests/Unit/Entity/GroupTest.php',
        ],
    ])
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true, privatization: true, naming: false, instanceOf: true, earlyReturn: true, carbon: false, rectorPreset: true, phpunitCodeQuality: true, doctrineCodeQuality: true, symfonyCodeQuality: true, symfonyConfigs: true)
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withAttributesSets(symfony: true, doctrine: true, sensiolabs: true)
;
