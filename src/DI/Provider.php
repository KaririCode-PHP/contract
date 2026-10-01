<?php

declare(strict_types=1);

namespace KaririCode\Contract\DI;

/**
 * Interface Provider.
 *
 * Defines the contract for service providers responsible for registering and bootstrapping services.
 *
 * @category  DependencyInjection
 *
 * @deprecated 2.11.0 Removed in 3.0.0; use ServiceProvider with BindableContainer (ADR-005).
 */
interface Provider
{
    /**
     * Registers services into the container.
     *
     * @param Container $container The dependency injection container
     */
    public function register(Container $container): void;

    /**
     * Boots services after registration.
     *
     * @param Container $container The dependency injection container
     */
    public function boot(Container $container): void;
}
