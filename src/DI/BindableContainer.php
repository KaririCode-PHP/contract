<?php

declare(strict_types=1);

namespace KaririCode\Contract\DI;

use KaririCode\Contract\Exception\ContainerFailure;
use KaririCode\Contract\Exception\NotFound;

/**
 * The container as service providers see it: bind a factory, a class or a
 * value to an identifier, and look one up.
 *
 * get() and has() repeat PSR-11's signatures instead of extending
 * Psr\Container\ContainerInterface, which would give this package a runtime
 * dependency (Spec V4.3.3 §31); implementations implement both.
 *
 * Preconditions: bind() takes a factory, which receives the container, a
 * class name to build, or null to build $abstract itself; with $singleton
 * the first result is reused. Bind while providers register; resolve during
 * boot() or later.
 *
 * Thread-safety: bindings are configuration, written while the application
 * boots and only read afterwards; never bind per request on a runtime that
 * reuses the process.
 *
 * Side effects: bind() and instance() change the container's configuration;
 * get() may build a service and, for singletons, keep it.
 *
 * Example:
 *
 *     $container->bind(RateLimiterRegistry::class, static fn (): RateLimiterRegistry => $registry, singleton: true);
 *     $container->instance('rate_limits.api', 600);
 *
 * References: PHP-FIG PSR-11; Fowler, "Inversion of Control Containers and
 * the Dependency Injection pattern" (2004).
 *
 * @see https://www.php-fig.org/psr/psr-11/
 */
interface BindableContainer
{
    /** @throws NotFound when $id is unknown, ContainerFailure when building fails */
    public function get(string $id): mixed;

    public function has(string $id): bool;

    /** @param callable|class-string|null $concrete */
    public function bind(string $abstract, callable|string|null $concrete, bool $singleton = false): void;

    public function instance(string $abstract, mixed $value): void;
}
