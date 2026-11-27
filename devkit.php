<?php

declare(strict_types=1);

/**
 * KaririCode Devkit — project overrides (ADR-008).
 *
 * The Spec V4.3.3 gates live in the root configs (phpunit.xml.dist,
 * phpstan.neon, psalm.xml, psalm-strict.xml, .php-cs-fixer.dist.php), so
 * kcode runs them instead of its generated defaults. The tools are pinned to
 * the versions the 2.12.0 gates passed with (Spec V4.3.3 §32).
 */

return [
    'root_configs' => true,

    'tools' => [
        'phpunit' => '11.5.56',
        'phpstan' => '2.2.16',
        'psalm' => '6.19.1',
        'php-cs-fixer' => '3.95.27',
    ],
];
