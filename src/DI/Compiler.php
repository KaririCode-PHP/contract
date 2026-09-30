<?php

declare(strict_types=1);

namespace KaririCode\Contract\DI;

/**
 * Interface Compiler.
 *
 * Defines the contract for compiling container definitions and handling caching mechanisms.
 *
 * @category  DependencyInjection
 */
interface Compiler
{
    /**
     * Compiles the container definitions.
     *
     * @param Container $container The container to compile
     *
     * @return Container Compiled container
     */
    public function compile(Container $container): Container;

    /**
     * Writes compiled container to a file.
     *
     * @param string $path Path to the file where compiled container will be written
     */
    public function writeToFile(string $path): void;

    /**
     * Loads compiled container definitions from a file.
     *
     * @param string $path Path to the file containing compiled container definitions
     *
     * @return Container Loaded container instance
     */
    public function loadFromFile(string $path): Container;
}
