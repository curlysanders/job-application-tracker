<?php

require_once __DIR__.'/tools/PhpCsFixer/ParameterLayoutFixer.php';

$customFixer = new CurlySanders\JobApplicationTracker\PhpCsFixer\ParameterLayoutFixer();

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ]);

return new PhpCsFixer\Config()
    ->registerCustomFixers([$customFixer])
    ->setRules([
        '@Symfony' => true,
        $customFixer->getName() => true,
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters']],
        'modifier_keywords' => ['elements' => ['method', 'const']],
    ])
    ->setFinder($finder);
