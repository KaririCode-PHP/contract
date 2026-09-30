<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor;

/**
 * Interface ProcessingResult.
 *
 * Defines the contract for handling processing results, including errors and processed data management.
 *
 * @category  Processor
 *
 * @see       https://kariricode.org/
 */
interface ProcessingResult
{
    /**
     * Adds an error associated with a specific property.
     *
     * @param string $property The property where the error occurred
     * @param string $errorKey A unique identifier for the type of error
     * @param string $message A human-readable error message
     */
    public function addError(string $property, string $errorKey, string $message): void;

    /**
     * Stores processed data for a specific property.
     *
     * @param string $property The property associated with the processed data
     * @param mixed $value The processed value to be stored
     */
    public function setProcessedData(string $property, mixed $value): void;

    /**
     * Checks if there are any errors in the processing result.
     *
     * @return bool True if there are errors, false otherwise
     */
    public function hasErrors(): bool;

    /**
     * Retrieves all errors that occurred during processing.
     *
     * @return array<string, array<int, array{errorKey: string, message: string}>>
     *                                                                             A map of property names to their associated errors
     */
    public function getErrors(): array;

    /**
     * Retrieves all processed data.
     *
     * @return array<string, mixed> A map of property names to their processed values
     */
    public function getProcessedData(): array;

    /**
     * Converts the processing result into an array representation.
     *
     * @return array{
     *     isValid: bool,
     *     errors: array<string, array<int, array{errorKey: string, message: string}>>,
     *     processedData: array<string, mixed>
     * }
     */
    public function toArray(): array;

    /**
     * Clears all stored errors and processed data.
     */
    public function clear(): void;
}
