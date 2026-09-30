<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

/**
 * Interface ConfigurableProcessor.
 *
 * Extends the Processor interface to allow configuration of the processor.
 *
 * @category  Processor
 *
 * @see       https://kariricode.org/
 */
interface ConfigurableProcessor extends Processor
{
    /**
     * Configures the processor with the provided options.
     *
     * @param array $options The configuration options
     */
    public function configure(array $options): void;
}
