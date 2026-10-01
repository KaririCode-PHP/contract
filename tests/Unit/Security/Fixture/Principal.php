<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Security\Fixture;

use KaririCode\Contract\Security\Identity;
use Override;

/**
 * Minimal Identity: readonly promoted properties satisfy the interface's
 * get-only properties; the anonymous caller is a named constructor.
 *
 * Example: `new Principal(subject: 'user-1', roles: ['admin'], scopes: [], claims: [])`
 */
final readonly class Principal implements Identity
{
    /**
     * @param list<string>         $roles
     * @param list<string>         $scopes
     * @param array<string, mixed> $claims
     */
    public function __construct(
        public string $subject,
        public array $roles,
        public array $scopes,
        public array $claims,
        private bool $anonymous = false,
    ) {}

    public static function anonymous(): self
    {
        return new self(subject: '', roles: [], scopes: [], claims: [], anonymous: true);
    }

    #[Override]
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    #[Override]
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    #[Override]
    public function hasAnyRole(string ...$roles): bool
    {
        return array_any($roles, $this->hasRole(...));
    }

    #[Override]
    public function hasAllScopes(string ...$scopes): bool
    {
        return array_all($scopes, $this->hasScope(...));
    }

    #[Override]
    public function claim(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->claims) ? $this->claims[$key] : $default;
    }

    #[Override]
    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }
}
