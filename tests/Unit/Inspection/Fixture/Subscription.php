<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidEmail;

/**
 * Fixture: a set hook that rejects a value with a Validation-family
 * exception, which the applier must let through. Example: `new Subscription('a@b.org')`
 */
final class Subscription
{
    public string $email {
        set(string $value) {
            if (!str_contains($value, '@')) {
                throw new InvalidEmail('The e-mail address has no "@".');
            }

            $this->email = $value;
        }
    }

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}
