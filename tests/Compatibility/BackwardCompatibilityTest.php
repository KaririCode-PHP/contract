<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Compatibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Proves that every release is additive: every type of the 2.8.6 and 2.9.0
 * snapshots still exists with exactly the same public API.
 *
 * Inherited types are frozen as a whole. A new method, parameter or parent
 * interface on an inherited interface would break its implementers, so it
 * is reported like any other difference; additions are new types only.
 *
 * Each fixture was captured by Reflection with dump-api.php before the next
 * release changed anything: 2.8.6 from the untouched Packagist dist, 2.9.0
 * from its release tarball. Docblocks are not part of them.
 */
final class BackwardCompatibilityTest extends TestCase
{
    private const array RELEASES = [
        '2.8.6' => ['reference' => '35935418be93009a1ce389460e6fbf87353f0dd7', 'types' => 54],
        '2.9.0' => ['reference' => 'sha256:86b11923ea913650a8c4a3e877be4c734057ace9b40d0e434ba985f1b2946217', 'types' => 59],
    ];

    /**
     * @return iterable<string, array{string}>
     */
    public static function releases(): iterable
    {
        foreach (array_keys(self::RELEASES) as $version) {
            yield $version => [$version];
        }
    }

    #[Test]
    #[DataProvider('releases')]
    public function everyTypeOfThePreviousReleaseKeepsItsPublicApi(string $version): void
    {
        $differences = self::differences(self::baseline($version), self::currentTypes());

        self::assertSame([], $differences, implode("\n", $differences));
    }

    #[Test]
    #[DataProvider('releases')]
    public function theFixtureIsTheSnapshotOfItsRelease(string $version): void
    {
        $fixture = self::fixture($version);

        self::assertSame('kariricode/contract', $fixture['package']);
        self::assertSame($version, $fixture['version']);
        self::assertSame(self::RELEASES[$version]['reference'], $fixture['reference']);
        self::assertIsArray($fixture['types']);
        self::assertCount(self::RELEASES[$version]['types'], $fixture['types']);
    }

    #[Test]
    public function anyDifferenceInAnInheritedTypeIsReported(): void
    {
        $baseline = self::baseline('2.8.6');
        self::assertIsArray($baseline['KaririCode\\Contract\\Processor\\Processor']);
        self::assertIsArray($baseline['KaririCode\\Contract\\DI\\Container']);

        $baseline['KaririCode\\Contract\\Processor\\Processor']['methods']['process']['return'] = 'string';
        unset($baseline['KaririCode\\Contract\\DI\\Container']['methods']['alias']);
        $baseline['KaririCode\\Contract\\Removed\\Type'] = ['kind' => 'interface'];

        self::assertSame(
            [
                'KaririCode\\Contract\\DI\\Container.methods.alias: added to an inherited type',
                'KaririCode\\Contract\\Processor\\Processor.methods.process.return: "string" -> "mixed"',
                'KaririCode\\Contract\\Removed\\Type: removed',
            ],
            self::differences($baseline, self::currentTypes()),
        );
    }

    /**
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $actual
     *
     * @return list<string>
     */
    private static function differences(array $expected, array $actual): array
    {
        $differences = [];

        foreach ($expected as $type => $record) {
            if (!array_key_exists($type, $actual)) {
                $differences[] = $type . ': removed';

                continue;
            }

            self::compare($type, $record, $actual[$type], $differences);
        }

        return $differences;
    }

    /**
     * @param list<string> $differences
     */
    private static function compare(string $path, mixed $expected, mixed $actual, array &$differences): void
    {
        if (!is_array($expected) || !is_array($actual)) {
            if ($expected !== $actual) {
                $differences[] = sprintf('%s: %s -> %s', $path, self::show($expected), self::show($actual));
            }

            return;
        }

        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                $differences[] = sprintf('%s.%s: removed', $path, $key);

                continue;
            }

            self::compare($path . '.' . $key, $value, $actual[$key], $differences);
        }

        foreach (array_keys(array_diff_key($actual, $expected)) as $key) {
            $differences[] = sprintf('%s.%s: added to an inherited type', $path, $key);
        }
    }

    private static function show(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseline(string $version): array
    {
        $types = self::fixture($version)['types'];
        self::assertIsArray($types);

        return $types;
    }

    /**
     * @return array<string, mixed>
     */
    private static function fixture(string $version): array
    {
        $json = file_get_contents(__DIR__ . '/fixtures/' . $version . '.json');
        self::assertIsString($json);

        $fixture = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($fixture);

        return $fixture;
    }

    /**
     * @return array<string, mixed>
     */
    private static function currentTypes(): array
    {
        return ApiSnapshot::capture(dirname(__DIR__, 2) . '/src')['types'];
    }
}
