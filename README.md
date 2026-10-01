# KaririCode\Contract

**The interfaces KaririCode components share, so that components depend on contracts instead of on each other.**

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.4-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![ARFA](https://img.shields.io/badge/ARFA-1.3%20level%201-orange)](docs/spec/SPEC-001-contract.md)
[![Runtime dependencies](https://img.shields.io/badge/runtime%20dependencies-0-brightgreen)](composer.json)

Part of the [KaririCode Framework](https://kariricode.org) ecosystem.

## Why

When one component needs a capability of another, depending on that other
package couples their release cycles and, sooner or later, creates cycles.
`kariricode/contract` holds only the interfaces: one package implements a
contract, others type against it, and neither depends on the other.

The package follows four rules:

- **Interfaces only.** No classes, enums, functions or implementations, and
  no runtime dependency beyond PHP.
- **Synchronous.** Nothing asynchronous lives here (ARFA 1.3, level 1);
  promises and adapters belong to `KaririCode\Async`.
- **A second real consumer first.** An interface enters when a second
  package actually uses it, with an ADR of its own (ADR-001). Where a
  PHP-FIG interface covers the need (cache, events, logging, container,
  clock), packages use the PSR.
- **Additive within 2.x.** Every 2.8.6 type is unchanged in 2.9.0, and a
  test compares the public API against a snapshot of 2.8.6 (ADR-002).

2.9.0 added the circuit breaker contract of `kariricode/circuit-breaker`
4.0.0 and the environment and configuration contracts of Configurator
ADR-007. 2.10.0 added the resilience ports a processor pipeline composes:
rate limiting (`kariricode/rate-limiter` 4.0.0), the manual permit protocol
of the circuit breaker, and retry policies (ADR-004). 2.11.0 adds the
cross-cutting contracts the Spec requires (ADR-005) and a verifiable supply
chain (ADR-006).

## Installation

```bash
composer require kariricode/contract:^2.11
```

Since 2.9.0 the package requires PHP 8.4 or newer, because interfaces
declare properties.
Projects on PHP 8.3 keep resolving 2.8.x within the same `^2.x`
constraint.

## Quick Start

Type against the contract; the implementation is injected.

```php
use KaririCode\Contract\Resilience\CallNotPermittedException;
use KaririCode\Contract\Resilience\CircuitBreaker;

final readonly class ExchangeRates
{
    public function __construct(
        private CircuitBreaker $breaker, // kariricode/circuit-breaker, or a test fake
        private RatesClient $client,
        private LastKnownRates $lastKnown,
    ) {}

    /** @return array<string, float> */
    public function current(): array
    {
        try {
            return $this->breaker->execute(fn(): array => $this->client->fetchRates());
        } catch (CallNotPermittedException $rejected) {
            return $this->lastKnown->rates(); // the client was not called
        }
    }
}
```

## Resilience

| Interface | Members | Implemented by |
|---|---|---|
| `Resilience\CircuitBreaker` (2.9.0) | `execute(callable $operation): mixed`, `isOpen(): bool` | `kariricode/circuit-breaker` 4.0.0 |
| `Resilience\CallNotPermittedException` (2.9.0) | extends `Exception\Runtime` since 2.11.0; `?int $retryAfterSeconds { get; }` | circuit-breaker's `CircuitOpenException` |
| `Resilience\ManualCircuitBreaker` (2.10.0) | extends `CircuitBreaker`; `tryAcquirePermit(): ?CallPermit`, `acquirePermit(): CallPermit`, `recordSuccess(CallPermit)`, `recordFailure(CallPermit, Throwable)`, `releasePermit(CallPermit)` | circuit-breaker 4.0.0, after the changes in SPEC-001 §8.4 |
| `Resilience\CallPermit` (2.10.0) | none: opaque proof of one admitted call | circuit-breaker's `Core\CallPermit` |
| `Resilience\RateLimiter` (2.10.0) | `attempt(string $key, int $cost = 1)`, `peek(string $key)`, `reset(string $key)` | `kariricode/rate-limiter` 4.0.0 |
| `Resilience\RateLimitDecision` (2.10.0) | get-only `bool $allowed`, `int $limit`, `int $remaining`, `int $retryAfter`, `int $resetAfter` | rate-limiter's `RateLimitResult` |
| `Resilience\RetryPolicy` (2.10.0) | `shouldRetry(Throwable $error, int $attempt): bool`, `delayMilliseconds(Throwable $error, int $attempt): int` | none yet; planned as an adapter in `kariricode/async-helper` (ADR-004 amendment) |

- `execute()` runs the operation only when the call is admitted and returns
  its result; an exception thrown by the operation is rethrown unchanged. A
  rejection throws `CallNotPermittedException` without running the operation.
- The manual protocol is acquire, run, settle once. `tryAcquirePermit()`
  returns null instead of throwing; `releasePermit()` settles a permit whose
  call did not run. Pass every Throwable to `recordFailure()`: the breaker
  decides whether it counts.
- A rate limit is per key, with a cost. A denial is a value: nothing is
  consumed, and `retryAfter` says how many seconds until the same call can
  succeed.
- A retry policy only decides. The caller counts attempts (`$attempt` = calls
  already made, all failed) and waits `delayMilliseconds()`.
- `isOpen()` is advisory and has no side effect.

A decorator that puts the rate limit between admission and the call:

```php
$permit = $breaker->tryAcquirePermit();
if ($permit === null) {
    return $this->skip($stage);               // open, or half-open trials in use
}
if (!$limiter->attempt($stage)->allowed) {
    $breaker->releasePermit($permit);         // admitted, but the call will not run
    return $this->throttle($stage);
}
try {
    $output = $processor->process($input);
} catch (\Throwable $error) {
    $breaker->recordFailure($permit, $error);
    throw $error;
}
$breaker->recordSuccess($permit);
```

A `readonly` property satisfies an interface property; no hook is needed,
and none is allowed in readonly types (errata E-013, SPEC-001 §9):

```php
final class CircuitOpen extends \RuntimeException implements CallNotPermittedException
{
    public function __construct(public readonly ?int $retryAfterSeconds)
    {
        parent::__construct('Circuit open.');
    }
}
```

The contracts carry no transport data (ARFA 1.3 P4). The HTTP edge maps a
rejection to `503 Service Unavailable` and a denial to `429 Too Many
Requests`, both with `Retry-After` (RFC 9110, RFC 6585):

```php
} catch (CallNotPermittedException $rejected) {
    return $responses->createResponse(503)
        ->withHeader('Retry-After', (string) ($rejected->retryAfterSeconds ?? 1));
}
```

## Environment and Configuration

### Environment

`Environment\EnvironmentResolver` reads environment variables without
touching `$_ENV`, `$_SERVER` or `getenv()`: `get(string $name): ?string`
and `has(string $name): bool`.

- A resolver answers from one snapshot: within a request, the same name
  gives the same value.
- `has($name)` is true exactly when `get($name)` is not null; an empty
  string is a defined value.
- Implementations: Dotenv's `DotenvEnvironmentResolver`; Configurator's
  system, in-memory and composite resolvers.

```php
use KaririCode\Contract\Environment\EnvironmentResolver;

final readonly class DatabaseSettings
{
    public function __construct(private EnvironmentResolver $environment) {}

    public function dsn(): string
    {
        return $this->environment->get('DATABASE_URL') ?? 'sqlite::memory:';
    }
}
```

### Configuration

`Configuration\ValueProcessor` transforms configuration values while they
load: `supports(mixed $value): bool` decides, `process(mixed $value): mixed`
transforms and may throw `RuntimeException`. Processors form a chain in
registration order, so interpolation can produce `int:8080` and a cast can
turn it into `8080`:

```php
foreach ($processors as $processor) {
    if ($processor->supports($value)) {
        $value = $processor->process($value);
    }
}
```

`Configuration\ConfigProvider` lets a component declare what it needs:
`namespace(): string` (top-level key), `defaults(): array` and
`schema(): array` (key to type), both keyed relative to the namespace.
Provider defaults are the lowest layer; configuration files, environment
overlays and programmatic overrides win over them.

```php
final readonly class QueueConfigProvider implements ConfigProvider
{
    public function namespace(): string
    {
        return 'queue';
    }

    public function defaults(): array
    {
        return ['driver' => 'sync', 'workers' => 4];
    }

    public function schema(): array
    {
        return ['driver' => 'string', 'workers' => 'int'];
    }
}
```

## Cross-cutting Contracts

Five families added in 2.11.0 (ADR-005), required by Spec V4.3.3 §8.4, §9,
§12 and §31.

| Interface | Role |
|---|---|
| `Exception\ExceptionInterface` + `Validation`, `NotFound`, `Protocol`, `Configuration`, `ContainerFailure`, `Runtime` | every component exception implements one family; markers without members |
| `DI\ServiceProvider`, `DI\BindableContainer` | two-phase provider: `register()` binds, `boot()` resolves |
| `Security\Identity` | the authenticated principal: `subject`, `roles`, `scopes`, `claims`; an anonymous caller is an `Identity`, never null |
| `Context\ContextStorage` | per request, coroutine or fiber; slots keyed by type; the runtime clears them |
| `SerializableData` | marker: public readonly properties are the JSON form (E-001) |

An edge maps the families without knowing the component (RFC 9110):

| Family | HTTP |
|---|---|
| `Validation` | 422 |
| `NotFound` | 404 |
| `Protocol` | 400 |
| `Configuration`, `ContainerFailure` | 500 |
| `Runtime` | 503 with `Retry-After` when the delay is known (an open circuit), 500 otherwise |

```php
$context->store(Identity::class, $identity);    // authenticator
$identity = $context->find(Identity::class);     // any component that decides
$context->clear();                               // runtime, end of request
```

## Inherited Contracts

The 54 types of 2.8.6, the five added in 2.9.0 and the five added in 2.10.0
keep their names, namespaces and signatures. `tests/Compatibility` compares
them with Reflection snapshots of the 2.8.6 dist and of the 2.9.0 and
2.10.0 releases; any difference in an earlier type fails. A type may gain a supertype only when
the supertype brings no member (`CallNotPermittedException` gained
`Exception\Runtime` that way). Since 2.11.0, `DI\Provider`,
`DI\Container`, `DI\Definition` and `DI\Compiler` are deprecated, for
removal in 3.0.0; use `DI\ServiceProvider` with `DI\BindableContainer`.

| Namespace | Purpose |
|---|---|
| `ImmutableValue` (root) | marker for immutable value objects |
| `DI` | container, definitions, providers, compiler |
| `DataStructure`, `\Behavioral`, `\Structural` | collections, maps, sets, queues, stacks, heaps, trees and their behaviours |
| `Logging`, `\Behavioral`, `\Structural` | loggers, handlers, formatters, processors, levels, rotation |
| `Processor`, `\Attribute` | processors, pipelines, registry, builder, results, processor attributes |
| `Sanitizer`, `Serializer`, `Transformer`, `Validator` | single-purpose component contracts |

Some inherited types break Spec V4.0 rules and stay as they are until 3.0.0,
because fixing them would break implementers: one abstract class, two names,
24 getters without parameters and one `toArray()`. The conformance test names
each of them, and QD-V40-COMPLIANCE §5 lists them.

One of them affects users today: `Logging\LoggerExtendedInterface` is
declared in `LoggerExtended.php`, so Composer's PSR-4 autoloader cannot load
it (the case since 2.8.6). Do not type against it.

## All Contracts

76 types in 20 namespaces; the full signatures are in SPEC-001 §3.

| Namespace | Types | Since |
|---|---|---|
| `(root)` | `ImmutableValue` (≤ 2.8.6), `SerializableData` (2.11.0) | ≤ 2.8.6, 2.11.0 |
| `Configuration` | `ConfigProvider`, `ValueProcessor` | 2.9.0 |
| `Context` | `ContextStorage` | 2.11.0 |
| `DI` | `BindableContainer` (2.11.0), `Compiler` (≤ 2.8.6), `Container` (≤ 2.8.6), `Definition` (≤ 2.8.6), `Provider` (≤ 2.8.6), `ServiceProvider` (2.11.0) | 2.11.0, ≤ 2.8.6 |
| `DataStructure` | `Deque`, `Heap`, `Map`, `Queue`, `Set`, `Stack`, `Tree` | ≤ 2.8.6 |
| `DataStructure\Behavioral` | `Comparable`, `Countable`, `Indexable`, `IterableCollection`, `Iterator`, `Modifiable`, `Searchable`, `Sortable` | ≤ 2.8.6 |
| `DataStructure\Structural` | `BPlusTreeCollection`, `Collection`, `Serializable` | ≤ 2.8.6 |
| `Environment` | `EnvironmentResolver` | 2.9.0 |
| `Exception` | `Configuration`, `ContainerFailure`, `ExceptionInterface`, `NotFound`, `Protocol`, `Runtime`, `Validation` | 2.11.0 |
| `Logging` | `LogFormatter`, `LogHandler`, `LogLevel`, `LogProcessor`, `LogRotator`, `Logger`, `LoggerExtendedInterface`, `LoggingManager` | ≤ 2.8.6 |
| `Logging\Behavioral` | `Configurable`, `Loggable`, `LoggerConfigurable` | ≤ 2.8.6 |
| `Logging\Structural` | `ContextAware`, `FormatterAware`, `HandlerAware`, `LoggerAware`, `ProcessorAware` | ≤ 2.8.6 |
| `Processor` | `ConfigurableProcessor`, `Pipeline`, `ProcessingResult`, `Processor`, `ProcessorBuilder`, `ProcessorRegistry`, `ProcessorValidator`, `ValidatableProcessor` | ≤ 2.8.6 |
| `Processor\Attribute` | `BaseProcessorAttribute`, `CustomizableMessageAttribute`, `ProcessableAttribute` | ≤ 2.8.6 |
| `Resilience` | `CallNotPermittedException` (2.9.0), `CallPermit` (2.10.0), `CircuitBreaker` (2.9.0), `ManualCircuitBreaker` (2.10.0), `RateLimitDecision` (2.10.0), `RateLimiter` (2.10.0), `RetryPolicy` (2.10.0) | 2.9.0, 2.10.0 |
| `Sanitizer` | `Sanitizer` | ≤ 2.8.6 |
| `Security` | `Identity` | 2.11.0 |
| `Serializer` | `Serializer` | ≤ 2.8.6 |
| `Transformer` | `Transformer` | ≤ 2.8.6 |
| `Validator` | `Validator` | ≤ 2.8.6 |

## ARFA 1.3 Compliance

Conformance level 1 (`extra.arfa` in `composer.json`).

| Principle | In this package |
|---|---|
| P1 Immutable State Transformation | contracts declare no mutable state; the one property is read-only (`{ get; }`) |
| P2 Reactive Flow Composition | not required at level 1; contracts are synchronous, asynchronous composition lives in `KaririCode\Async` |
| P3 Adaptive Context Awareness | not required at level 1; `CallNotPermittedException` lets callers choose their fallback |
| P4 Protocol Agnosticism | no transport types: a rejection carries seconds, not an HTTP status |
| P5 Continuous Observability | belongs to implementations (the breaker publishes PSR-14 events); interfaces emit nothing |

The Spec V4.0 rules checked by `tests/Conformance/ArfaConformanceTest`:
interfaces only, no `Interface`/`Manager`/`Helper`/`Util` names, no getters
without parameters, no `toArray()`/`jsonSerialize()`, one type per file named
after it, typed constants, no hooks in readonly types (E-013), no authorship
tags in docblocks, no runtime dependency, ARFA and ASVS levels declared;
for the types added since 2.10.0, documentation criteria C1–C5 (Spec V4.3.3
§17) and synchronous signatures; exception families as markers without
members under one root; deprecations that name their removal. The quality
record is **PENDING**
until the gates run in CI.

## Ecosystem Position

```
 implemented by                     contract                  consumed by
 circuit-breaker 4.0.0 ───────────▶ CircuitBreaker      ◀─── dataflow, observability, processor-pipeline
                                    ManualCircuitBreaker ◀── processor-pipeline, dataflow (async stages), async
 rate-limiter 4.0.0 ──────────────▶ RateLimiter         ◀─── processor-pipeline, observability, dataflow, authenticator
 async-helper (planned adapter) ──▶ RetryPolicy         ◀─── processor-pipeline, queue; dataflow, observability
 authenticator ───────────────────▶ Security\Identity   ◀─── authorization, router, graphql, grpc, websocket, sse
 runtime, observability ──────────▶ Context\ContextStorage ◀─ observability, tenancy, authenticator
 every component ─────────────────▶ Exception\*         ◀─── runtime and HTTP error handling
 dependency-injection ────────────▶ DI\BindableContainer ◀── every component's ServiceProvider
 dotenv, configurator ────────────▶ Environment         ◀─── configurator (interpolation)
 components, configurator ────────▶ Configuration       ◀─── configurator (builder, value chain)
 configurator (ConfigurationProcessor) ─▶ Processor     ◀─── processor-pipeline (registry)
```

Cache and events between packages go through PSR-16 and PSR-14, not
through this package (ADR-001).

On Packagist, checked 2026-09-30, the latest tagged version of each
`kariricode/*` package that requires the contract asks for `^2.0` to `^2.8`:
data-structure `^2.0`, configurator and logging `^2.6`, validator `^2.7`,
processor-pipeline and property-inspector `^2.8`. 2.11.0 satisfies all of
them on PHP 8.4. Migration of the consumers: SPEC-001 §8.

## Architecture

```
src/<Namespace>/<Type>.php     one interface per file, PSR-4
tests/<2.8.6 namespaces>/      inherited tests (suite "inherited")
tests/Unit/                    minimal implementations of each new interface
tests/Compatibility/           2.8.6 API snapshot and the comparison test
tests/Conformance/             ARFA 1.3 / Spec V4.0 rules
docs/adr/  docs/spec/  docs/quality/
```

- The package depends on PHP only; every other package depends on it, never
  the reverse.
- The API snapshots (`tests/Compatibility/fixtures/2.8.6.json`,
  `2.9.0.json` and `2.10.0.json`) were captured by Reflection before the next
  release changed anything; `dump-api.php` regenerates them.
- Inherited source and tests are frozen: PHP CS Fixer skips them, and the
  PHPStan and Psalm baselines hold only findings in inherited files.

- Supply chain (ADR-006): `composer.lock` pins the development toolchain;
  `tools/sbom.php` writes a CycloneDX 1.5 SBOM; `.github/workflows/ci.yml`
  runs every gate with actions pinned by commit SHA.

## Metrics

Measured on 2026-10-01 in a local sandbox with PHP 8.4.23 and the locked
toolchain, not in CI.

| Metric | Value |
|---|---|
| Types | 76 (75 interfaces, 1 inherited abstract class), 20 namespaces |
| Public methods | 172 (138 from 2.8.6, 9 from 2.9.0, 10 from 2.10.0, 15 from 2.11.0); interface properties: 10 |
| PHP lines in `src/` | 3,113 (443 new in 2.11.0) |
| Runtime dependencies | 0 |
| Tests | 260 tests, 3,938 assertions, all passing |
| PHPStan (level max) | 0 errors; baseline of 46 findings in 17 inherited files |
| Psalm (error level 2) | 0 errors; baseline of 10 findings in 6 inherited files |
| Psalm (error level 1, strict) | 0 errors, no baseline, over the source added since 2.9.0 (`composer psalm-strict`) |
| Known vulnerabilities | none in the locked toolchain (`composer security`) |
| SBOM | CycloneDX 1.5: 0 runtime, 95 development components (`composer sbom`) |
| Line coverage | not measured (no coverage driver available) |

## License

MIT. See [LICENSE](LICENSE).
