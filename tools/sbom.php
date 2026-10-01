<?php

declare(strict_types=1);

/*
 * Writes a CycloneDX 1.5 SBOM of this package from composer.json and
 * composer.lock (Spec V4.3.3 §32). Runtime packages are "required"; the
 * development toolchain is "excluded" because it never ships (§19.6).
 *
 * Usage: php tools/sbom.php [output]. The version comes from
 * COMPOSER_ROOT_VERSION (a leading "v" is dropped) and defaults to dev-main.
 */

$root = dirname(__DIR__);

$read = static function (string $file): array {
    $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

    return is_array($data) ? $data : throw new RuntimeException($file . ' is not a JSON object.');
};

$licenses = static fn(mixed $ids): array => array_map(
    static fn(mixed $id): array => ['license' => preg_match('/^[A-Za-z0-9.+-]+$/', (string) $id) === 1 ? ['id' => (string) $id] : ['name' => (string) $id]],
    array_values((array) $ids),
);

$component = static function (array $package, string $scope) use ($licenses): array {
    $name = (string) ($package['name'] ?? '');
    $version = ltrim((string) ($package['version'] ?? ''), 'v');
    [$group, $short] = array_pad(explode('/', $name, 2), 2, '');
    $purl = sprintf('pkg:composer/%s@%s', $name, $version);
    $entry = ['type' => 'library', 'bom-ref' => $purl, 'group' => $group, 'name' => $short, 'version' => $version, 'scope' => $scope, 'purl' => $purl];

    if (($package['license'] ?? []) !== []) {
        $entry['licenses'] = $licenses($package['license']);
    }

    $shasum = $package['dist']['shasum'] ?? '';
    if (is_string($shasum) && $shasum !== '') {
        $entry['hashes'] = [['alg' => 'SHA-1', 'content' => $shasum]];
    }

    return $entry;
};

$composer = $read($root . '/composer.json');
$lock = $read($root . '/composer.lock');
$runtime = (array) ($lock['packages'] ?? []);
$development = (array) ($lock['packages-dev'] ?? []);

$version = ltrim((string) (getenv('COMPOSER_ROOT_VERSION') ?: 'dev-main'), 'v');
$name = (string) ($composer['name'] ?? '');
[$group, $short] = array_pad(explode('/', $name, 2), 2, '');
$purl = sprintf('pkg:composer/%s@%s', $name, $version);

$bytes = random_bytes(16);
$bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
$bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

$bom = [
    'bomFormat' => 'CycloneDX',
    'specVersion' => '1.5',
    'serialNumber' => 'urn:uuid:' . vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)),
    'version' => 1,
    'metadata' => [
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'tools' => ['components' => [['type' => 'application', 'author' => 'KaririCode', 'name' => 'tools/sbom.php']]],
        'component' => ['type' => 'library', 'bom-ref' => $purl, 'group' => $group, 'name' => $short, 'version' => $version, 'purl' => $purl, 'licenses' => $licenses($composer['license'] ?? [])],
    ],
    'components' => [
        ...array_map(static fn(mixed $package): array => $component((array) $package, 'required'), $runtime),
        ...array_map(static fn(mixed $package): array => $component((array) $package, 'excluded'), $development),
    ],
];

$output = $argv[1] ?? 'sbom.cdx.json';
file_put_contents($output, json_encode($bom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
fwrite(STDOUT, sprintf("%s: %s %s, %d runtime and %d development components\n", $output, $name, $version, count($runtime), count($development)));
