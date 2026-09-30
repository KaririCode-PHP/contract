<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

/**
 * Interface ProcessorBuilder.
 *
 * Defines the contract for a builder that constructs processors and processor pipelines.
 * This builder allows for the creation of individual processors and the assembly of
 * multiple processors into a pipeline, based on given specifications and contexts.
 *
 * @category  ProcessorPipeline
 *
 * @see       https://kariricode.org/
 */
interface ProcessorBuilder
{
    /**
     * Builds a single processor.
     *
     * This method constructs and configures a processor based on the provided
     * context, name, and configuration options.
     *
     * @param string $context The context in which the processor is being built
     * @param string $name The name of the processor to build
     * @param array $processorConfig Configuration options for the processor
     *
     * @throws \RuntimeException If the processor cannot be built or configured
     *
     * @return Processor The constructed processor instance
     */
    public function build(string $context, string $name, array $processorConfig = []): Processor;

    /**
     * Builds a pipeline of processors.
     *
     * This method constructs a pipeline by building and assembling multiple
     * processors based on the provided specifications and context.
     *
     * @param string $context The context in which the pipeline is being built
     * @param array $processorSpecs An array of processor specifications
     *
     * @throws \RuntimeException If the pipeline cannot be built or if any processor fails to construct
     *
     * @return Pipeline The constructed pipeline instance containing the specified processors
     */
    public function buildPipeline(string $context, array $processorSpecs): Pipeline;
}
