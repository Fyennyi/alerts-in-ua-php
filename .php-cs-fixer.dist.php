<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'return_type_declaration' => ['space_before' => 'one'],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'single_line_empty_body' => true,
        'no_whitespace_in_blank_line' => true,
        'no_trailing_whitespace' => true,
        'single_blank_line_at_eof' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'blank_line_between_import_groups' => false,
        'no_unused_imports' => true,
        'phpdoc_align' => [
            'align' => 'vertical',
        ],
        'phpdoc_separation' => [
            'groups' => [
                ['template', 'inheritDoc'],
                ['param', 'return', 'throws'],
                ['see', 'link', 'since', 'deprecated'],
            ],
            'skip_unlisted_annotations' => false,
        ],
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_indent' => true,
        'no_superfluous_phpdoc_tags' => false,
        'phpdoc_add_missing_param_annotation' => false,
        'phpdoc_summary' => false,
        'yoda_style' => [
            'equal' => true,
            'identical' => true,
            'less_and_greater' => false,
            'always_move_variable' => false,
        ],
    ]);