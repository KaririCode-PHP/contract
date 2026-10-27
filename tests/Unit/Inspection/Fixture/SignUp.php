<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Validate;

/**
 * Fixture: a readonly DPO, the case a copy-on-write applier exists for; its
 * e-mail mixes rule kinds so declaration order can be observed.
 * Example: `new SignUp(email: ' A@B.org ', name: ' Walmir ', age: 30)`
 */
final readonly class SignUp extends Account
{
    public function __construct(
        #[Sanitize('trim')]
        #[Validate('email')]
        #[Sanitize('lowercase')]
        public string $email,
        #[Sanitize('trim')]
        private string $name,
        public int $age,
        string $tenant = ' acme ',
    ) {
        parent::__construct($tenant);
    }

    public function name(): string
    {
        return $this->name;
    }
}
