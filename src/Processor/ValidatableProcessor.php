<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

/**
 * Interface ValidatableProcessor.
 *
 * Defines the contract for processors that include validation capabilities.
 * A ValidatableProcessor is a specialized Processor that can perform validation
 * during its execution, and may return specific error details if validation fails.
 *
 * This interface extends the basic Processor contract by adding methods to check
 * for validity and retrieve validation error information.
 *
 * @category  ProcessorPipeline
 *
 * @see       https://kariricode.org/
 */
interface ValidatableProcessor extends Processor
{
    /**
     * Resets the processor's state to its initial values.
     *
     * This method should be called before reusing the processor instance to ensure
     * that any previous validation state is cleared and the processor is ready for
     * new validation. This is particularly important when processors are reused
     * across multiple validation cycles.
     */
    public function reset(): void;

    /**
     * Checks if the processor is in a valid state.
     *
     * This method determines whether the processor has met all required
     * conditions and can continue processing. A processor that fails validation
     * may halt the processing pipeline or trigger corrective actions.
     *
     * @return bool true if the processor is valid, false otherwise
     */
    public function isValid(): bool;

    /**
     * Retrieves the error key if the processor fails validation.
     *
     * When a processor is deemed invalid by the isValid method, this method
     * returns a key that uniquely identifies the validation error. This key
     * can be used for logging, error messages, or further handling within the
     * processing pipeline.
     *
     * @return string|null the error key, or null if no validation error occurred
     */
    public function getErrorKey(): ?string;
}
