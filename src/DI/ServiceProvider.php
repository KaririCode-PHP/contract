<?php

declare(strict_types=1);

namespace KaririCode\Contract\DI;

/**
 * A component's entry point into the application's container: it declares
 * the component's services, then wires what needs the finished container.
 *
 * Preconditions: register() only binds and never resolves, because other
 * providers may not have registered yet; boot() runs once, after every
 * provider registered, and may resolve. A provider keeps the container it
 * received in register() if boot() needs it.
 *
 * Thread-safety: called once per application boot, before any request is
 * served.
 *
 * Side effects: register() writes bindings; boot() may resolve services and
 * attach listeners, routes or commands.
 *
 * Example:
 *
 *     final class RateLimiterServiceProvider implements ServiceProvider
 *     {
 *         public function register(BindableContainer $container): void
 *         {
 *             $container->bind(RateLimiterRegistry::class, $this->registry, singleton: true);
 *         }
 *
 *         public function boot(): void {}
 *     }
 *
 * References: Spec V4.3.3 §31 (the Provider/ directory of every component);
 * Laravel, "Service Providers" (the two-phase register/boot life cycle).
 *
 * @see https://laravel.com/docs/providers
 */
interface ServiceProvider
{
    public function register(BindableContainer $container): void;

    public function boot(): void;
}
