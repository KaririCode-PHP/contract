<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

/**
 * Interface Pipeline.
 *
 * Defines the contract for a pipeline that can execute a series of processors.
 *
 * @category  Processor
 *
 * @see       https://kariricode.org/
 */
interface Pipeline
{
    /**
     * Adds a processor to the pipeline.
     *
     * @param Processor $processor The processor to be added
     */
    public function addProcessor(Processor $processor): self;

    /**
     * Executes the processing pipeline on the provided input.
     *
     * @param mixed $input The value to be processed by the pipeline
     *
     * @return mixed The final result after passing through all processors
     */
    public function process(mixed $input): mixed;
}
