<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Context\Fixture;

/** Fixture value stored in the context. Example: `new TenantContext('t-1')` */
final readonly class TenantContext
{
    public function __construct(public string $id) {}
}
