<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Inspection\PropertyAttributeHandler;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidEmail;
use Override;

/** Fixture handler that rejects the first value it sees with a Validation-family exception. */
final class RejectingHandler implements PropertyAttributeHandler
{
    #[Override]
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): void
    {
        throw new InvalidEmail(sprintf('%s is not a valid e-mail address.', $propertyName));
    }
}
