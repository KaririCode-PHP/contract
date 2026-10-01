# ADR-005: Shared cross-cutting contracts

- **Status:** Proposed (target: 2.11.0)
- **Date:** 2026-10-01
- **Component:** kariricode/contract
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

A review of the 25 KaririCode component records (2026-10-01) against Spec
V4.3.3 found abstractions that several components share but that live
outside this package, or nowhere:

| Concern | Today | Problem |
|---|---|---|
| Service providers | `KaririCode\Framework\Contract\ServiceProvider` + `BindableContainer` (kariricode/framework), used by Tenancy, CircuitBreaker, RateLimiter and Runtime; `DependencyInjection\Provider\ServiceProvider`, typed against the concrete `Container`; `Contract\DI\Provider` + `Container` (inherited from 2.8.6), implemented by no V4 component | three definitions; components cannot depend on the framework, so each one stubs it in its tests and nothing checks the signatures |
| Exceptions | each component has its own base (`RateLimiterException`, `QueueException`, `AuthorizationException`, …) | the edge cannot tell a 404 from a 422 or a 500 without knowing every component; Spec V4.3.3 §8.4 says a `Contract\Exception\*` hierarchy already exists, and it does not |
| Identity | `KaririCode\Authenticator\Contract\Identity` | Authorization requires `kariricode/authenticator` directly; Router guards and the GraphQL, gRPC, WebSocket and SSE packages need the same type |
| Serialization marker | Spec V4.3.3 §9 defines `KaririCode\Contract\SerializableData`, which E-001 puts in place of `jsonSerialize()` | absent; 2.9.0 left it out for lack of an implementer |
| Request context | Observability `Contract\ContextStorage` (trace only, with a static implementation), Tenancy `TenantRegistry` (mutable singleton), Authenticator (principal) | Spec V4.3.3 §12 requires request context behind one `ContextStorage` in the contract, so that level-2 runtimes do not leak tenant, trace or principal into the next request |

Spec V4.3.3 §31 routes all inter-component coupling through
`kariricode/contract` and says the package provides `Result`,
`SerializableData`, `Clock`, `RandomBytes` and `ContextStorage`.

## Decision

Add five families in 2.11.0, all additive.

### 1. Exception families (`KaririCode\Contract\Exception`), Spec §8.4

```php
interface ExceptionInterface extends \Throwable {}
interface Validation extends ExceptionInterface {}
interface NotFound extends ExceptionInterface {}
interface Configuration extends ExceptionInterface {}
interface Protocol extends ExceptionInterface {}
interface ContainerFailure extends ExceptionInterface {}
interface Runtime extends ExceptionInterface {}
```

Markers without members: the numeric code is `getCode()`, taken from the
component's range. `ExceptionInterface` keeps the suffix under E-035
(sanctioned root contract). Each exception implements one family. The
contract carries no transport data (P4); the edge maps families to
responses, for example:

| Family | Meaning | HTTP |
|---|---|---|
| `Validation` | input rejected by validation | 422 |
| `NotFound` | `get()` of an absent item; `find()` returns null instead (E-036) | 404 |
| `Protocol` | malformed or unsupported wire input | 400 |
| `Configuration` | misconfiguration found at boot or build | 500 |
| `ContainerFailure` | a service could not be built | 500 |
| `Runtime` | infrastructure fault while running | 500 or 503 |

### 2. Service providers (`KaririCode\Contract\DI`)

```php
interface BindableContainer
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
    public function bind(string $abstract, callable|string|null $concrete, bool $singleton = false): void;
    public function instance(string $abstract, mixed $value): void;
}

interface ServiceProvider
{
    public function register(BindableContainer $container): void;
    public function boot(): void;
}
```

Same names and signatures as `KaririCode\Framework\Contract`, so migrating
is an import change. `BindableContainer` repeats PSR-11's `get()` and
`has()` instead of extending `Psr\Container\ContainerInterface`, which would
add a runtime dependency (§31); implementations implement both, which PHP
allows because the signatures are identical. `register()` only binds;
`boot()` runs after every provider has registered. `DI\Provider`,
`DI\Container`, `DI\Definition` and `DI\Compiler` become `@deprecated`, for
removal in 3.0.0.

### 3. Identity (`KaririCode\Contract\Security`)

```php
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
```

The Authenticator's contract, unchanged, parameter names included: PHP binds
named arguments to them. An anonymous caller is an `Identity` whose
`isAnonymous()` is true, never null.

### 4. Serialization marker (`KaririCode\Contract`), Spec §9 and E-001

```php
interface SerializableData {}
```

The object is safe for `json_encode()` through its public readonly
properties. Precondition: they hold scalars, arrays, backed enums or other
`SerializableData`; pure enums, closures and resources make `json_encode()`
fail.

### 5. Request context (`KaririCode\Contract\Context`), Spec §12

```php
interface ContextStorage
{
    /**
     * @template T of object
     * @param class-string<T> $type
     * @param T $value
     */
    public function store(string $type, object $value): void;

    /**
     * @template T of object
     * @param class-string<T> $type
     * @return T|null
     */
    public function find(string $type): ?object;

    public function clear(): void;
}
```

One slot per type, keyed by the class or interface the writer names
(`store(Identity::class, $identity)`), so readers ask for the contract, not
the implementation. Values are immutable. An implementation keeps one
context per request, coroutine or fiber, and the runtime calls `clear()`
when a request ends. `find()` returns null when the slot is empty (E-036).

## Relation to ADR-001

| Family | Implemented by | Consumed by |
|---|---|---|
| Exception | the exceptions of every component | Runtime's error handler, Http and Router error middleware |
| DI | dependency-injection's `Container` | every component's provider; the framework kernel |
| Identity | authenticator | authorization, router, graphql, grpc, websocket, sse |
| SerializableData | value objects across components | serializer, HTTP responders |
| ContextStorage | runtime (per driver), observability | observability, tenancy, authenticator, authorization |

`SerializableData` and `ContextStorage` enter on the Spec's mandate (§9,
§12, §31), with their consumers identified but not yet migrated. ADR-001 is
amended to accept a Spec mandate as a reason to enter, still with an ADR.

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Keep providers in kariricode/framework | components may not depend on the framework (§31); they stub it and nothing checks the signatures |
| `BindableContainer extends Psr\Container\ContainerInterface` | needs `psr/container` at runtime, breaking the zero-dependency rule; declaring two methods keeps implementations PSR-11 compatible |
| Implement the inherited `DI\Container` | no V4 container does; it forces tagging, aliases, calls and extension on every implementer (interface segregation) |
| Exceptions with members (range, context) | `getCode()` already carries the code; context belongs to the concrete class |
| Key/value context (`set(string $key, mixed $value)`) | untyped keys bring back stringly-typed coupling; typed slots let static analysis check `find(Identity::class)` |
| Slots keyed by the stored object's class | readers would need the implementation class instead of the contract |
| Identity as a value object in the contract | the package holds interfaces only (ADR-001) |

## Out of Scope (separate ADRs)

| Topic | Why not now |
|---|---|
| Metrics port or PSR-14 events | two patterns coexist: AsyncHelper's `MetricsCollector` and the events of CircuitBreaker, RateLimiter and Authorization; one must be chosen first |
| `Async\Promise`, `Async\CancellationToken` (level 2) | consumed only inside the async family today |
| Runtime `Sandboxable` | `ContextStorage::clear()` covers most of it; revisit after the migration |
| `Result<T, E>` | optional in §8.6, and no component uses it |
| `Clock`, `RandomBytes` | PSR-20 and PHP's `Random\Randomizer` already cover them; propose an erratum to §31 instead |

## Consequences

### Positive

- Components drop the framework stubs, and Authorization drops its direct
  dependency on the Authenticator.
- The edge maps failures by family instead of answering 500 to everything.
- Level-2 runtimes get one place to clear per-request state.

### Negative

- 2.11.0 adds twelve interfaces; each family needs unit fixtures and
  conformance coverage.
- Four inherited DI interfaces become deprecated, and their users see
  deprecation notices in static analysis.
- Components migrate one at a time; until then two provider contracts
  coexist.

## Open Points

1. Namespaces `Security` (Identity) and `Context` (ContextStorage).
2. Whether `CallNotPermittedException` (2.9.0) should also extend
   `Exception\Runtime`. Adding a parent without members breaks no
   implementer or consumer, but the compatibility test must then accept
   added parents.
3. Whether to deprecate the inherited DI interfaces in 2.11.0 or leave them
   untouched until 3.0.0.

## ARFA 1.3

- **P1:** identity, context values and exception markers carry no mutable
  state.
- **P2:** not affected (level 1).
- **P3:** `ContextStorage` gives every component the current tenant,
  principal and trace.
- **P4:** exceptions classify failures; HTTP mapping stays at the edge.
- **P5:** trace context reaches logs and metrics through `ContextStorage`.

## References

- KaririCode Spec V4.3.3 §8.4, §9, §12, §17, §31; errata E-001, E-035, E-036.
- PHP-FIG, PSR-11 (Container Interface) and PSR-20 (Clock).
- OpenID Foundation, OpenID Connect Core 1.0, §2 (ID Token claims).
- OpenTelemetry Specification, "Context".
- Martin, R. C. (2017). *Clean Architecture*, ch. 10 (Interface Segregation).
- Design records of kariricode/framework, dependency-injection,
  authenticator, observability and tenancy.
