<?php

declare(strict_types=1);

namespace KaririCode\Contract\DI;

/**
 * Interface Container.
 *
 * Defines the contract for a dependency injection container that manages the creation and resolution of services.
 *
 * @category  DependencyInjection
 *
 * @deprecated 2.11.0 Removed in 3.0.0; providers bind through BindableContainer, and applications resolve through PSR-11 (ADR-005).
 */
interface Container
{
    /**
     * Sets a service in the container.
     *
     * @param string $id Identifier of the service
     * @param callable $service The callable that returns the service instance
     */
    public function set(string $id, callable $service): void;

    /**
     * Retrieves a service by its identifier.
     *
     * @param string $id Identifier of the service to retrieve
     *
     * @return mixed The service instance
     */
    public function get(string $id): mixed;

    /**
     * Checks if the container can return a service for the given identifier.
     *
     * @param string $id Identifier of the service to check
     *
     * @return bool True if the service is found, false otherwise
     */
    public function has(string $id): bool;

    /**
     * Resolves and returns an instance of the given abstract type.
     *
     * @param string $abstract The abstract type or identifier to resolve
     *
     * @return object Resolved instance of the given abstract
     */
    public function resolve(string $abstract): object;

    /**
     * Binds a concrete implementation to an abstract type with optional parameters.
     *
     * @param string $abstract The abstract type or identifier
     * @param mixed $concrete The concrete implementation (class name or closure)
     * @param array $parameters Optional parameters for instantiation
     */
    public function bind(string $abstract, mixed $concrete, array $parameters = []): void;

    /**
     * Registers a singleton binding, ensuring the instance is created only once.
     *
     * @param string $abstract The abstract type or identifier
     * @param mixed $concrete The concrete implementation (class name or closure)
     * @param array $parameters Optional parameters
     */
    public function singleton(string $abstract, mixed $concrete, array $parameters = []): void;

    /**
     * Registers a factory binding, creating a new instance upon each resolution.
     *
     * @param string $abstract The abstract type or identifier
     * @param mixed $concrete The concrete implementation (class name or closure)
     * @param array $parameters Optional parameters
     */
    public function factory(string $abstract, mixed $concrete, array $parameters = []): void;

    /**
     * Assigns multiple abstracts to a specific tag.
     *
     * @param string $tag Tag name
     * @param array $abstracts List of abstract identifiers
     */
    public function tag(string $tag, array $abstracts): void;

    /**
     * Retrieves abstracts associated with a specific tag.
     *
     * @param string $tag Tag name
     *
     * @return array Abstract identifiers tagged
     */
    public function tagged(string $tag): array;

    /**
     * Executes a callable or method with provided parameters.
     *
     * @param callable|string $callback The callable or method name
     * @param array $parameters Parameters to pass
     *
     * @return mixed Result of the callable execution
     */
    public function call(callable|string $callback, array $parameters = []): mixed;

    /**
     * Extends an existing binding.
     *
     * @param string $abstract Abstract identifier to extend
     * @param \Closure $extender Closure to extend functionality
     */
    public function extend(string $abstract, \Closure $extender): void;

    /**
     * Creates an alias for an abstract identifier.
     *
     * @param string $abstract Original abstract identifier
     * @param string $alias Alias to use
     */
    public function alias(string $abstract, string $alias): void;
}
