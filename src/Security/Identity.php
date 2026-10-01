<?php

declare(strict_types=1);

namespace KaririCode\Contract\Security;

/**
 * The authenticated principal of the current call, as every component that
 * decides about it sees it: who (subject), what they may do (roles,
 * scopes) and what the credential asserted (claims).
 *
 * Preconditions: subject is the stable identifier the authenticator issued
 * (the OIDC `sub`), never a display name; roles and scopes have no
 * duplicates; claims hold verified claims only. An anonymous caller is an
 * Identity whose isAnonymous() is true and whose roles and scopes are empty,
 * never null. hasAllScopes() without arguments is true.
 *
 * Thread-safety: implementations are immutable; share one instance among
 * everything that serves the call, never across calls.
 *
 * Side effects: none; every check only reads the identity.
 *
 * Example:
 *
 *     if (!$identity->hasScope('orders:write')) {
 *         return $this->forbidden();
 *     }
 *     $tenant = $identity->claim('tenant_id');
 *
 * References: OpenID Connect Core 1.0 §2 (ID Token, `sub`); RFC 6749 §3.3
 * (scope); RFC 9068 (JWT access tokens).
 *
 * @see https://openid.net/specs/openid-connect-core-1_0.html#IDToken
 */
interface Identity
{
    public string $subject { get; }

    /** @var list<string> */
    public array $roles { get; }

    /** @var list<string> */
    public array $scopes { get; }

    /** @var array<string, mixed> */
    public array $claims { get; }

    public function hasRole(string $role): bool;

    public function hasScope(string $scope): bool;

    public function hasAnyRole(string ...$roles): bool;

    public function hasAllScopes(string ...$scopes): bool;

    public function claim(string $key, mixed $default = null): mixed;

    public function isAnonymous(): bool;
}
