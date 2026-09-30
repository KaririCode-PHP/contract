<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

/**
 * Interface ProcessorValidator.
 *
 * Defines the contract for validating processors within the processing pipeline.
 * A ProcessorValidator is responsible for ensuring that a processor is in a valid state
 * before allowing it to proceed with its task. If validation fails, the validator returns
 * an array containing error details, which can be used to handle the failure.
 *
 * This interface supports custom validation messages, allowing flexibility in
 * error reporting based on the context or type of processor.
 *
 * @category  ProcessorPipeline
 *
 * @see       https://kariricode.org/
 */
interface ProcessorValidator
{
    /**
     * Validates a processor and returns any validation errors.
     *
     * This method checks whether the given processor meets the required validation
     * criteria. If the processor is invalid, it returns an array containing the error
     * details, including a custom error message if provided. Otherwise, it returns null.
     *
     * @param Processor $processor the processor to be validated
     * @param string $processorName the unique name of the processor, used to identify it in the context of validation
     * @param array $messages an array of custom messages for validation errors, keyed by processor name
     *
     * @return array|null the validation error details, or null if validation passes
     */
    public function validate(Processor $processor, string $processorName, array $messages): ?array;
}
