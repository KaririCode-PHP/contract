<?php

declare(strict_types=1);

// Code inherited from 2.8.6 (src and tests) is frozen until 3.0.0 (ADR-002):
// reformatting it would change code lines, so the fixer covers the rest.
$inherited = ['DI', 'DataStructure', 'Logging', 'Processor', 'Sanitizer', 'Serializer', 'Transformer', 'Validator'];

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/tools'])
    ->exclude($inherited)
    ->notPath('ImmutableValue.php')
    // Files added since 2.11.0 inside a frozen directory are new code.
    ->append([__DIR__ . '/src/DI/BindableContainer.php', __DIR__ . '/src/DI/ServiceProvider.php']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    ])
    ->setFinder($finder);
