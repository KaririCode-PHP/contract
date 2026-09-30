<?php

declare(strict_types=1);

namespace KaririCode\Contract\Environment;

/**
 * Reads environment variables without touching $_ENV, $_SERVER or getenv()
 * directly.
 *
 * The one contract through which components read the environment:
 * Configurator (interpolation), Dotenv (the loaded .env store), the DI
 * container and CLI overrides. Depending on it instead of on the process
 * globals lets tests supply a fixed environment and lets sources be
 * composed (a .env store falling back to the system, a secrets vault in
 * front of both).
 *
 * Implementations answer from one snapshot: within a request, repeated
 * calls with the same name return the same value, and has($name) is true
 * exactly when get($name) is not null.
 *
 * Example: `$dsn = $environment->get('DATABASE_URL') ?? 'sqlite::memory:';`
 */
interface EnvironmentResolver
{
    /**
     * Value of the variable, or null when this resolver does not define it.
     */
    public function get(string $name): ?string;

    /**
     * Whether this resolver defines the variable.
     */
    public function has(string $name): bool;
}
