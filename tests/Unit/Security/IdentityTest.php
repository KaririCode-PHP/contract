<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Security;

use KaririCode\Contract\Security\Identity;
use KaririCode\Contract\Tests\Unit\Security\Fixture\Principal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class IdentityTest extends TestCase
{
    #[Test]
    public function checksOnlyReadTheIdentity(): void
    {
        $identity = self::editor();

        self::assertTrue($identity->hasRole('editor'));
        self::assertFalse($identity->hasRole('admin'));
        self::assertTrue($identity->hasScope('orders:read'));
        self::assertTrue($identity->hasAnyRole('admin', 'editor'));
        self::assertFalse($identity->hasAnyRole());
        self::assertTrue($identity->hasAllScopes('orders:read', 'orders:write'));
        self::assertFalse($identity->hasAllScopes('orders:read', 'billing:read'));
    }

    #[Test]
    public function hasAllScopesWithoutArgumentsIsTrue(): void
    {
        self::assertTrue(Principal::anonymous()->hasAllScopes());
    }

    #[Test]
    public function aClaimFallsBackToTheDefaultOnlyWhenAbsent(): void
    {
        $identity = new Principal(subject: 'user-1', roles: [], scopes: [], claims: ['tenant_id' => 't-9', 'nickname' => null]);

        self::assertSame('t-9', $identity->claim('tenant_id'));
        self::assertNull($identity->claim('nickname', 'fallback'), 'a null claim is present');
        self::assertSame('fallback', $identity->claim('locale', 'fallback'));
    }

    #[Test]
    public function anAnonymousCallerIsAnIdentityWithNothingGranted(): void
    {
        $anonymous = Principal::anonymous();

        self::assertInstanceOf(Identity::class, $anonymous);
        self::assertTrue($anonymous->isAnonymous());
        self::assertSame([], $anonymous->roles);
        self::assertSame([], $anonymous->scopes);
        self::assertFalse(self::editor()->isAnonymous());
    }

    #[Test]
    public function aReadonlyClassSatisfiesThePropertiesWithoutHooks(): void
    {
        $contract = new ReflectionClass(Identity::class);

        foreach ($contract->getProperties() as $declared) {
            self::assertSame(['get'], array_keys($declared->getHooks()), $declared->getName());
            self::assertTrue((new ReflectionProperty(Principal::class, $declared->getName()))->isReadOnly(), $declared->getName());
        }

        self::assertCount(4, $contract->getProperties());
    }

    private static function editor(): Principal
    {
        return new Principal(subject: 'user-1', roles: ['editor'], scopes: ['orders:read', 'orders:write'], claims: []);
    }
}
