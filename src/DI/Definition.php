<?php

declare(strict_types=1);

namespace KaririCode\Contract\DI;

/**
 * Interface Definition.
 *
 * Defines the contract for service definitions managed by the dependency injection container.
 *
 * @category  DependencyInjection
 */
interface Definition
{
    /**
     * Gets the concrete implementation.
     *
     * @return mixed The concrete implementation of the service
     */
    public function getConcrete(): mixed;

    /**
     * Gets the instantiation parameters.
     *
     * @return array Parameters used for service instantiation
     */
    public function getParameters(): array;

    /**
     * Gets tags associated with the definition.
     *
     * @return array List of tags associated with this definition
     */
    public function getTags(): array;

    /**
     * Checks if definition is a singleton.
     *
     * @return bool True if the definition is a singleton, false otherwise
     */
    public function isSingleton(): bool;

    /**
     * Checks if definition is a factory.
     *
     * @return bool True if the definition is a factory, false otherwise
     */
    public function isFactory(): bool;
}
