<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();
$config->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_blank_lines_before_namespace' => true,
        'new_with_braces' => true,
        'no_whitespace_in_blank_line' => true,
        'strict_param' => true,        
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
    ])
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setLineEnding("\n");

return $config;
