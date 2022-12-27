<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('node_modules')
    ->exclude('vendor')
    ->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'array_indentation' => true,
        'binary_operator_spaces' => [
            'operators' => [
                '=' => 'single_space',
                '=>' => null,
            ]
        ],
        'blank_line_before_statement' => true,
        'cast_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
        'indentation_type' => true,
        'linebreak_after_opening_tag' => true,
        'lowercase_static_reference' => false,
        'method_chaining_indentation' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_spaces_around_offset' => true,
        'no_whitespace_before_comma_in_array' => true,
        'normalize_index_brace' => true,
        'trim_array_spaces' => true,
        'visibility_required' => ['elements' => ['property', 'method']],
    ])
    ->setFinder($finder);
