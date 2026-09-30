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

2.9.0 adds five interfaces that already have implementations and consumers:
the resilience contracts of `kariricode/circuit-breaker` 4.0.0 and the
environment and configuration contracts of Configurator ADR-007.

## Installation

```bash
composer require kariricode/contract:^2.9
```

2.9.0 requires PHP 8.4 or newer, because interfaces declare properties.
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
| `Resilience\CircuitBreaker` | `execute(callable $operation): mixed`, `isOpen(): bool` | `kariricode/circuit-breaker` 4.0.0 |
| `Resilience\CallNotPermittedException` | extends `Throwable`; `?int $retryAfterSeconds { get; }` | `CircuitOpenException` in the same package |

- `execute()` runs the operation only when the call is admitted and returns
  its result; an exception thrown by the operation is rethrown unchanged.
- A rejected call throws `CallNotPermittedException` without running the
  operation. `retryAfterSeconds` is the delay in whole seconds, or null when
  unknown.
- `isOpen()` is advisory and has no side effect; `execute()` stays the
  authority.

A `readonly` property satisfies the interface property; no hook is needed,
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

The contract carries no transport data (ARFA 1.3 P4). The HTTP edge maps a
rejection to `503 Service Unavailable` with `Retry-After` (RFC 9110):

```php
} catch (CallNotPermittedException $rejected) {
    return $responses->createResponse(503)
        ->withHeader('Retry-After', (string) ($rejected->retryAfterSeconds ?? 1));
}
```

## Environment

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

## Configuration

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

## Inherited Contracts

The 54 types of 2.8.6 keep their names, namespaces and signatures.
`tests/Compatibility` compares them with a Reflection snapshot of the 2.8.6
dist; any difference in an inherited type fails.

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

59 types in 17 namespaces; the full signatures are in SPEC-001 §3.

| Namespace | Types | Since |
|---|---|---|
| `(root)` | `ImmutableValue` | ≤ 2.8.6 |
| `Configuration` | `ConfigProvider`, `ValueProcessor` | 2.9.0 |
| `DI` | `Compiler`, `Container`, `Definition`, `Provider` | ≤ 2.8.6 |
| `DataStructure` | `Deque`, `Heap`, `Map`, `Queue`, `Set`, `Stack`, `Tree` | ≤ 2.8.6 |
| `DataStructure\Behavioral` | `Comparable`, `Countable`, `Indexable`, `IterableCollection`, `Iterator`, `Modifiable`, `Searchable`, `Sortable` | ≤ 2.8.6 |
| `DataStructure\Structural` | `BPlusTreeCollection`, `Collection`, `Serializable` | ≤ 2.8.6 |
| `Environment` | `EnvironmentResolver` | 2.9.0 |
| `Logging` | `LogFormatter`, `LogHandler`, `LogLevel`, `LogProcessor`, `LogRotator`, `Logger`, `LoggerExtendedInterface`, `LoggingManager` | ≤ 2.8.6 |
| `Logging\Behavioral` | `Configurable`, `Loggable`, `LoggerConfigurable` | ≤ 2.8.6 |
| `Logging\Structural` | `ContextAware`, `FormatterAware`, `HandlerAware`, `LoggerAware`, `ProcessorAware` | ≤ 2.8.6 |
| `Processor` | `ConfigurableProcessor`, `Pipeline`, `ProcessingResult`, `Processor`, `ProcessorBuilder`, `ProcessorRegistry`, `ProcessorValidator`, `ValidatableProcessor` | ≤ 2.8.6 |
| `Processor\Attribute` | `BaseProcessorAttribute`, `CustomizableMessageAttribute`, `ProcessableAttribute` | ≤ 2.8.6 |
| `Resilience` | `CallNotPermittedException`, `CircuitBreaker` | 2.9.0 |
| `Sanitizer` | `Sanitizer` | ≤ 2.8.6 |
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
tags in docblocks, no runtime dependency. The quality record is **PENDING**
until the gates run in CI.

## Ecosystem Position

```
 implemented by                     contract                consumed by
 circuit-breaker 4.0.0 ───────────▶ Resilience     ◀─────── dataflow, observability, processor-pipeline
 dotenv, configurator  ───────────▶ Environment    ◀─────── configurator (interpolation)
 components, configurator ────────▶ Configuration  ◀─────── configurator (builder, value chain)
 configurator (ConfigurationProcessor) ─▶ Processor ◀────── processor-pipeline (registry)
```

Cache and events between packages go through PSR-16 and PSR-14, not
through this package (ADR-001).

On Packagist, checked 2026-09-30, the latest tagged version of each
`kariricode/*` package that requires the contract asks for `^2.0` to `^2.8`:
data-structure `^2.0`, configurator and logging `^2.6`, validator `^2.7`,
processor-pipeline and property-inspector `^2.8`. 2.9.0 satisfies all of
them on PHP 8.4. Migration of the 2.9 consumers: SPEC-001 §8.

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
- The API snapshot (`tests/Compatibility/fixtures/2.8.6.json`) was captured
  by Reflection from the untouched 2.8.6 dist before any change;
  `dump-api.php` regenerates it.
- Inherited source and tests are frozen: PHP CS Fixer skips them, and the
  PHPStan and Psalm baselines hold only findings in inherited files.

## Metrics

Measured on 2026-09-30 in a local sandbox with PHP 8.4.23, not in CI.

| Metric | Value |
|---|---|
| Types | 59 (58 interfaces, 1 inherited abstract class), 17 namespaces |
| Public methods | 147 (138 inherited, 9 new); interface properties: 1 |
| PHP lines in `src/` | 2,373 (186 new) |
| Runtime dependencies | 0 |
| Tests | 195 tests, 500 assertions, all passing |
| PHPStan (level max) | 0 errors; baseline of 46 findings in 17 inherited files |
| Psalm (error level 2) | 0 errors; baseline of 10 findings in 6 inherited files |
| Line coverage | not measured (no coverage driver available) |

## License

MIT. See [LICENSE](LICENSE).
