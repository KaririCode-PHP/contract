<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

use KaririCode\Contract\DataStructure\Map;

/**
 * Interface ProcessorRegistry.
 *
 * Defines the contract for a registry that manages processors within specific contexts.
 * This registry allows for the registration, retrieval, and management of processors
 * organized by context and name.
 *
 * @category  ProcessorPipeline
 *
 * @see       https://kariricode.org/
 */
interface ProcessorRegistry
{
    /**
     * Registers a processor within a specific context.
     *
     * This method allows for the addition of a processor to the registry,
     * associating it with a given context and name for later retrieval.
     *
     * @param string $context The context under which the processor is registered
     * @param string $name The unique name of the processor within the context
     * @param Processor $processor The processor instance to be registered
     *
     * @return self Returns the current instance to enable a fluent interface
     */
    public function register(string $context, string $name, Processor $processor): self;

    /**
     * Retrieves a processor by its context and name.
     *
     * This method fetches a previously registered processor based on its
     * associated context and name.
     *
     * @param string $context The context of the processor to retrieve
     * @param string $name The name of the processor to retrieve
     *
     * @throws \RuntimeException If the processor is not found in the specified context
     *
     * @return Processor The requested processor instance
     */
    public function get(string $context, string $name): Processor;

    /**
     * Retrieves all processors registered under a specific context.
     *
     * This method returns a HashMap containing all processors associated
     * with the given context.
     *
     * @param string $context The context for which to retrieve processors
     *
     * @throws \RuntimeException If the specified context is not found in the registry
     *
     * @return HashMap A HashMap containing all processors in the specified context
     */
    public function getContextProcessors(string $context): Map;
}
