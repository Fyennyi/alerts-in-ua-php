<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

$config = new PhpCsFixer\Config();

return $config->setRules([
    'ordered_imports' => ['sort_algorithm' => 'alpha'],
    'no_whitespace_in_blank_line' => true,
    'no_trailing_whitespace' => true,
    'no_unused_imports' => true,
    'single_blank_line_at_eof' => true,
])
    ->setLineEnding("\n")
    ->setFinder($finder);
