<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony:risky' => true,
        '@PHP81Migration' => true,
    ])
    ->setFinder($finder)
;
