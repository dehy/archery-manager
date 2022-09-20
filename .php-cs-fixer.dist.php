<?php

require_once __DIR__ . "/tools/PrettierPHPFixer.php";

$finder = (new PhpCsFixer\Finder())->in(__DIR__)->exclude("var");

return (new PhpCsFixer\Config())
    ->registerCustomFixers([new PrettierPHPFixer()])
    ->setRules([
        "Prettier/php" => true,
        "@Symfony" => true,
    ])
    ->setFinder($finder);
