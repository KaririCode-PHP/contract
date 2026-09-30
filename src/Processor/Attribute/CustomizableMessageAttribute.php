<?php

declare(strict_types=1);

namespace KaririCode\Contract\Processor\Attribute;

/**
 * Interface CustomizableMessageAttribute.
 *
 * Defines the contract for an attribute that can provide custom error messages for validators.
 *
 * @category  Processor
 *
 * @see       https://kariricode.org/
 */
interface CustomizableMessageAttribute
{
    /**
     * Retrieves a custom message for a specific validator.
     *
     * @param string $validatorName The name of the validator
     *
     * @return string|null The custom message if set, null otherwise
     */
    public function getMessage(string $validatorName): ?string;
}
