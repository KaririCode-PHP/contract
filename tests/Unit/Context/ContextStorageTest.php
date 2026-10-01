<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Context;

use Fiber;
use InvalidArgumentException;
use KaririCode\Contract\Security\Identity;
use KaririCode\Contract\Tests\Unit\Context\Fixture\FiberContextStorage;
use KaririCode\Contract\Tests\Unit\Context\Fixture\TenantContext;
use KaririCode\Contract\Tests\Unit\Security\Fixture\Principal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ContextStorageTest extends TestCase
{
    #[Test]
    public function aReaderAsksForTheTypeTheWriterNamed(): void
    {
        $context = new FiberContextStorage();
        $principal = new Principal(subject: 'user-1', roles: [], scopes: [], claims: []);

        $context->store(Identity::class, $principal);

        self::assertSame($principal, $context->find(Identity::class));
        self::assertNull($context->find(Principal::class), 'slots are keyed by the named type, not the class');
    }

    #[Test]
    public function anEmptySlotReadsAsNull(): void
    {
        self::assertNull((new FiberContextStorage())->find(TenantContext::class));
    }

    #[Test]
    public function storeReplacesTheSlotAndClearEmptiesEveryOne(): void
    {
        $context = new FiberContextStorage();
        $context->store(TenantContext::class, new TenantContext('t-1'));
        $context->store(TenantContext::class, new TenantContext('t-2'));

        self::assertSame('t-2', $context->find(TenantContext::class)?->id);

        $context->clear();

        self::assertNull($context->find(TenantContext::class));
    }

    #[Test]
    public function aValueMustBeAnInstanceOfItsType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FiberContextStorage())->store(Identity::class, new TenantContext('t-1'));
    }

    #[Test]
    public function eachFiberSeesOnlyItsOwnContext(): void
    {
        $context = new FiberContextStorage();
        $context->store(TenantContext::class, new TenantContext('main'));

        $fiber = new Fiber(static function () use ($context): ?string {
            $before = $context->find(TenantContext::class)?->id;
            $context->store(TenantContext::class, new TenantContext('fiber'));
            Fiber::suspend($before);

            return $context->find(TenantContext::class)?->id;
        });

        self::assertNull($fiber->start(), 'a new fiber starts empty');
        self::assertSame('main', $context->find(TenantContext::class)?->id, 'the fiber did not leak into the main flow');

        $fiber->resume();

        self::assertSame('fiber', $fiber->getReturn());
    }
}
