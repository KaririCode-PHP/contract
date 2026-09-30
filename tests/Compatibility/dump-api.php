<?php

declare(strict_types=1);

/*
 * Writes the public-API snapshot of a source tree to stdout.
 *
 * Usage: php tests/Compatibility/dump-api.php <src-dir> <version> <dist-reference>
 *
 * The 2.8.6 fixture was produced from the untouched Packagist dist:
 *   php tests/Compatibility/dump-api.php /path/to/2.8.6/src 2.8.6 35935418be93009a1ce389460e6fbf87353f0dd7 \
 *     > tests/Compatibility/fixtures/2.8.6.json
 */

require __DIR__ . '/ApiSnapshot.php';

use KaririCode\Contract\Tests\Compatibility\ApiSnapshot;

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php dump-api.php <src-dir> <version> <dist-reference>\n");
    exit(2);
}

$api = ApiSnapshot::capture($argv[1]);

echo ApiSnapshot::toJson([
    'package' => 'kariricode/contract',
    'version' => $argv[2],
    'reference' => $argv[3],
    'php' => PHP_VERSION,
    'types' => $api['types'],
    'files' => $api['files'],
]);
