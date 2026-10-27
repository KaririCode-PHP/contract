<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;

/**
 * Fixture: a readonly parent DPO with an inherited protected property (inspected
 * through its children) and a private one (owned by this class, never inspected).
 * Example: `final readonly class SignUp extends Account`
 */
abstract readonly class Account
{
    public function __construct(
        #[Sanitize('trim')]
        protected string $tenant,
        #[Sanitize('never-seen')]
        private string $audit = 'created',
    ) {}

    public function tenant(): string
    {
        return $this->tenant;
    }

    public function audit(): string
    {
        return $this->audit;
    }
}
