<?php

declare(strict_types=1);

namespace KaririCode\Contract\Configuration;

/**
 * Lets a component declare the configuration it needs: its top-level key,
 * its defaults and the expected type of each key.
 *
 * A configuration builder registers providers before it reads any file, so
 * provider defaults are the lowest layer:
 *
 *   1. provider defaults (this contract)
 *   2. configuration files
 *   3. environment overlays (app.production.php)
 *   4. programmatic overrides
 *
 * User configuration therefore always wins over component defaults.
 *
 * Example: `$builder->withProvider(new CacheConfigProvider());`
 */
interface ConfigProvider
{
    /**
     * Top-level key of the component's configuration: 'cache' yields
     * 'cache.driver', 'cache.ttl'.
     */
    public function namespace(): string;

    /**
     * Default values, keyed relative to namespace().
     *
     * @return array<string, mixed>
     */
    public function defaults(): array;

    /**
     * Expected type of each key, relative to namespace().
     *
     * @return array<string, string> e.g. ['driver' => 'string', 'ttl' => 'int']
     */
    public function schema(): array;
}
