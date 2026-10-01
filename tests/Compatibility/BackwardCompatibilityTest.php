<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Compatibility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Proves that every release is additive: every type of the 2.8.6, 2.9.0
 * and 2.10.0 snapshots still exists with exactly the same public API.
 *
 * Inherited types are frozen as a whole. A new method, parameter or parent
 * interface on an inherited interface would break its implementers, so it
 * is reported like any other difference; additions are new types only.
 *
 * Each fixture was captured by Reflection with dump-api.php before the next
 * release changed anything: 2.8.6 from the untouched Packagist dist, 2.9.0
 * and 2.10.0 from their release tarballs. Docblocks are not part of them.
 */
final class BackwardCompatibilityTest extends TestCase
{
    private const array RELEASES = [
        '2.8.6' => ['reference' => '35935418be93009a1ce389460e6fbf87353f0dd7', 'types' => 54],
        '2.9.0' => ['reference' => 'sha256:86b11923ea913650a8c4a3e877be4c734057ace9b40d0e434ba985f1b2946217', 'types' => 59],
        '2.10.0' => ['reference' => 'sha256:7197a54377da3aaee7d3c0f82f01ff0686e312cf08a8bd099ac6d9c39bc6cbb4', 'types' => 64],
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
    public function aSupertypeMayBeAddedOnlyWhenItBringsNoMember(): void
    {
        $type = 'KaririCode\\Contract\\Resilience\\CallNotPermittedException';
        $baseline = self::baseline('2.10.0');

        self::assertSame([], self::differences([$type => $baseline[$type]], self::currentTypes()), 'Exception\\Runtime adds no member');

        $narrowed = $baseline[$type];
        self::assertIsArray($narrowed);
        $narrowed['interfaces'] = ['Stringable'];

        self::assertNotSame([], self::differences([$type => $narrowed], self::currentTypes()), 'gaining Throwable would add getMessage() and the rest');
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
        if (str_ends_with($path, '.interfaces') && is_array($expected) && is_array($actual)) {
            self::compareSupertypes($path, $expected, $actual, $differences);

            return;
        }

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

    /**
     * A type may gain a supertype only when the supertype brings no member the
     * type did not already have (a marker such as Exception\Runtime); losing
     * one is always a break.
     *
     * @param array<array-key, mixed> $expected
     * @param array<array-key, mixed> $actual
     * @param list<string>            $differences
     */
    private static function compareSupertypes(string $path, array $expected, array $actual, array &$differences): void
    {
        $before = array_values(array_filter($expected, 'is_string'));
        $after = array_values(array_filter($actual, 'is_string'));

        foreach (array_diff($before, $after) as $removed) {
            $differences[] = sprintf('%s: %s removed', $path, $removed);
        }

        $known = [];
        foreach ($before as $name) {
            if (interface_exists($name)) {
                $known = [...$known, ...self::memberNames($name)];
            }
        }

        foreach (array_diff($after, $before) as $added) {
            if (!interface_exists($added) || array_diff(self::memberNames($added), $known) !== []) {
                $differences[] = sprintf('%s: %s added with new members', $path, $added);
            }
        }
    }

    /**
     * @param class-string $interface
     *
     * @return list<string>
     */
    private static function memberNames(string $interface): array
    {
        $type = new \ReflectionClass($interface);

        return [
            ...array_map(static fn(\ReflectionMethod $method): string => $method->getName() . '()', $type->getMethods()),
            ...array_map(static fn(\ReflectionProperty $property): string => '$' . $property->getName(), $type->getProperties()),
        ];
    }
}
