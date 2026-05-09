<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->notPath('config/reference.php')
    ->notPath('config/bundles.php')
    ->notPath('config/preload.php')
    ->notPath('public/index.php')
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@auto:risky' => true,
    ])
    ->setFinder($finder)
    ;
