<?php

declare(strict_types=1);

namespace KaririCode\Contract\Validator;

/**
 * Interface Validator.
 *
 * Defines the contract for a validator that can check the validity of input data.
 *
 * @category  Validator
 *
 * @see       https://kariricode.org/
 */
interface Validator
{
    /**
     * Validates the provided input and returns the validated result.
     *
     * @param mixed $input The value to be validated
     *
     * @return mixed The validated result
     */
    public function validate(mixed $input): mixed;
}
