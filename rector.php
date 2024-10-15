<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(php83: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, codingStyle: true, typeDeclarations: true, privatization: true, naming: false, instanceOf: true, earlyReturn: true, strictBooleans: true, carbon: false, rectorPreset: true, phpunitCodeQuality: true, doctrineCodeQuality: true, symfonyCodeQuality: true, symfonyConfigs: true, twig: true, phpunit: true)
    ->withAttributesSets(symfony: true, doctrine: true, sensiolabs: true)
;
