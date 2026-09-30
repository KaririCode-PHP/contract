# SPEC-001: kariricode/contract 2.9.0

| Field | Value |
|---|---|
| Status | Accepted (content); quality gate PENDING — see QD-V40-COMPLIANCE |
| Date | 2026-09-30 |
| Component | kariricode/contract 2.9.0 |
| Authors | Walmir Silva <walmir.silva@kariricode.org> |
| Standards | ARFA 1.3 (level 1); KaririCode Code Quality Directive V4.0 |
| Error codes | none: the package declares interfaces and throws nothing |
| PHP | ^8.4 |
| Base | 2.8.6 Packagist dist, reference `35935418be93009a1ce389460e6fbf87353f0dd7` |

## 1. Scope

`kariricode/contract` holds the interfaces that KaririCode packages share,
so that one package implements a contract and others type against it
without depending on each other (ADR-001). 2.9.0 is the 2.8.6 package plus
five interfaces, with no change to any inherited type (ADR-002).

## 2. New API

```php
namespace KaririCode\Contract\Resilience;

interface CircuitBreaker
{
    /** @template T @param callable(): T $operation @return T @throws CallNotPermittedException */
    public function execute(callable $operation): mixed;

    public function isOpen(): bool;
}

interface CallNotPermittedException extends \Throwable
{
    public ?int $retryAfterSeconds { get; }
}

namespace KaririCode\Contract\Environment;

interface EnvironmentResolver
{
    public function get(string $name): ?string;

    public function has(string $name): bool;
}

namespace KaririCode\Contract\Configuration;

interface ValueProcessor
{
    public function supports(mixed $value): bool;

    /** @throws \RuntimeException when the value cannot be processed */
    public function process(mixed $value): mixed;
}

interface ConfigProvider
{
    public function namespace(): string;

    /** @return array<string, mixed> */
    public function defaults(): array;

    /** @return array<string, string> */
    public function schema(): array;
}
```

| Member | Semantics | Verified by |
|---|---|---|
| `CircuitBreaker::execute()` | runs the operation only when admitted and returns its result unchanged; an exception of the operation is rethrown unchanged; a rejected call throws `CallNotPermittedException` without running the operation | `CircuitBreakerTest` (3 tests) |
| `CircuitBreaker::isOpen()` | advisory, no side effect; remote-backed implementations may perform I/O | `CircuitBreakerTest::isOpenHasNoSideEffect` |
| `CallNotPermittedException::$retryAfterSeconds` | whole seconds until a retry may be admitted; null when unknown; read-only | `CallNotPermittedExceptionTest` |
| `EnvironmentResolver::get()` | the value, or null when this resolver does not define the name; one snapshot per request | `EnvironmentResolverTest` |
| `EnvironmentResolver::has()` | true exactly when `get()` is not null; an empty string is defined | `EnvironmentResolverTest::hasIsTrueExactlyWhenGetIsNotNull` |
| `ValueProcessor::supports()` | called before `process()`; decides whether the value concerns the processor | `ValueProcessorTest` |
| `ValueProcessor::process()` | called only after `supports()` returned true; returning the input unchanged is valid; throws `RuntimeException` when the value cannot be processed | `ValueProcessorTest` |
| `ConfigProvider::namespace()` | one top-level key (`cache` gives `cache.driver`) | `ConfigProviderTest` |
| `ConfigProvider::defaults()`, `schema()` | keyed relative to the namespace; defaults are the lowest configuration layer, below files, environment overlays and overrides | `ConfigProviderTest` |

Processors form a chain applied in registration order to every scalar value
after parsing and before validation; a later processor sees the output of
an earlier one (`ValueProcessorTest::aChainAppliesEachSupportingProcessorInOrder`).

## 3. Inventory

59 types in 17 namespaces: 58 interfaces and one inherited abstract class.
147 public methods (138 inherited, 9 new) and one interface property (new).

### 3.1 Namespaces

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

### 3.2 Types and members

Generated by Reflection from `src/`; `Extends / implements` lists direct
parents only.

**`(root)`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `ImmutableValue` | interface | — | — (marker) |

**`Configuration`** — new in 2.9.0

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `ConfigProvider` | interface | — | `namespace(): string`<br>`defaults(): array`<br>`schema(): array` |
| `ValueProcessor` | interface | — | `supports(mixed $value): bool`<br>`process(mixed $value): mixed` |

**`DI`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Compiler` | interface | — | `compile(DI\Container $container): DI\Container`<br>`writeToFile(string $path): void`<br>`loadFromFile(string $path): DI\Container` |
| `Container` | interface | — | `set(string $id, callable $service): void`<br>`get(string $id): mixed`<br>`has(string $id): bool`<br>`resolve(string $abstract): object`<br>`bind(string $abstract, mixed $concrete, array $parameters = []): void`<br>`singleton(string $abstract, mixed $concrete, array $parameters = []): void`<br>`factory(string $abstract, mixed $concrete, array $parameters = []): void`<br>`tag(string $tag, array $abstracts): void`<br>`tagged(string $tag): array`<br>`call(callable|string $callback, array $parameters = []): mixed`<br>`extend(string $abstract, Closure $extender): void`<br>`alias(string $abstract, string $alias): void` |
| `Definition` | interface | — | `getConcrete(): mixed`<br>`getParameters(): array`<br>`getTags(): array`<br>`isSingleton(): bool`<br>`isFactory(): bool` |
| `Provider` | interface | — | `register(DI\Container $container): void`<br>`boot(DI\Container $container): void` |

**`DataStructure`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Deque` | interface | `DataStructure\Queue` | `addFirst(mixed $element): void`<br>`removeLast(): mixed`<br>`peekLast(): mixed` |
| `Heap` | interface | — | `add(mixed $element): void`<br>`insert(int $index, mixed $element): void`<br>`poll(): mixed`<br>`remove(mixed $element): bool`<br>`peek(): mixed` |
| `Map` | interface | `DataStructure\Behavioral\Countable`, `DataStructure\Behavioral\IterableCollection` | `put(mixed $key, mixed $value): void`<br>`get(mixed $key): mixed`<br>`remove(mixed $key): bool`<br>`containsKey(mixed $key): bool`<br>`keys(): array`<br>`values(): array`<br>`clear(): void` |
| `Queue` | interface | `DataStructure\Behavioral\Modifiable` | `enqueue(mixed $element): void`<br>`dequeue(): mixed`<br>`peek(): mixed` |
| `Set` | interface | `DataStructure\Behavioral\Modifiable`, `DataStructure\Behavioral\Searchable`, `\Traversable` | `union(DataStructure\Set $other): DataStructure\Set`<br>`intersection(DataStructure\Set $other): DataStructure\Set`<br>`difference(DataStructure\Set $other): DataStructure\Set` |
| `Stack` | interface | — | `push(mixed $element): void`<br>`pop(): mixed`<br>`peek(): mixed` |
| `Tree` | interface | `DataStructure\Behavioral\Modifiable`, `DataStructure\Behavioral\Searchable` | `insert(int $index, mixed $value): void` |

**`DataStructure\Behavioral`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Comparable` | interface | — | `compareTo(mixed $other): int` |
| `Countable` | interface | — | `size(): int` |
| `Indexable` | interface | — | `get(mixed $index): mixed`<br>`set(mixed $index, mixed $element): void` |
| `IterableCollection` | interface | `\Traversable` | `getIterator(): Iterator` |
| `Iterator` | interface | — | `next(): mixed`<br>`hasNext(): bool` |
| `Modifiable` | interface | — | `add(mixed $element): void`<br>`remove(mixed $element): bool`<br>`clear(): void` |
| `Searchable` | interface | — | `contains(mixed $element): bool`<br>`find(mixed $element): mixed` |
| `Sortable` | interface | — | `sort(): void` |

**`DataStructure\Structural`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `BPlusTreeCollection` | interface | `DataStructure\Behavioral\Sortable`, `DataStructure\Structural\Collection`, `DataStructure\Tree` | `getOrder(): int`<br>`rangeSearch(mixed $start, mixed $end): array`<br>`getMinimum(): mixed`<br>`getMaximum(): mixed`<br>`balance(): void` |
| `Collection` | interface | `DataStructure\Behavioral\Countable`, `DataStructure\Behavioral\Indexable`, `DataStructure\Behavioral\Modifiable`, `DataStructure\Behavioral\Searchable` | `addAll(DataStructure\Structural\Collection $collection): void`<br>`getItems(): array` |
| `Serializable` | interface | — | `serialize(): mixed`<br>`deserialize(mixed $data): void` |

**`Environment`** — new in 2.9.0

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `EnvironmentResolver` | interface | — | `get(string $name): ?string`<br>`has(string $name): bool` |

**`Logging`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `LogFormatter` | interface | — | `format(ImmutableValue $record): string`<br>`formatBatch(array $records): string` |
| `LogHandler` | interface | — | `handle(ImmutableValue $record): void`<br>`isHandling(ImmutableValue $record): bool` |
| `LogLevel` | interface | — | `getLevel(): string`<br>`getColor(): string`<br>`getValue(): int` |
| `LogProcessor` | interface | — | `process(ImmutableValue $record): ImmutableValue` |
| `LogRotator` | interface | — | `shouldRotate(string $filePath): bool`<br>`rotate(string $filename): void` |
| `Logger` | interface | `Logging\Behavioral\Loggable` | `getName(): string` |
| `LoggerExtendedInterface` | interface | `Logging\Logger` | `addHandler(Logging\Structural\HandlerAware $handler): self`<br>`addProcessor(Logging\Structural\ProcessorAware $processor): self`<br>`setFormatter(Logging\Structural\FormatterAware $formatter): self`<br>`getName(): string` |
| `LoggingManager` | interface | `Logging\Behavioral\Configurable`, `Logging\Behavioral\Loggable`, `Logging\Structural\ContextAware`, `Logging\Structural\FormatterAware`, `Logging\Structural\HandlerAware`, `Logging\Structural\ProcessorAware` | `getName(): string` |

**`Logging\Behavioral`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Configurable` | interface | — | `getConfig(): ImmutableValue`<br>`setConfig(ImmutableValue $config): Logging\Behavioral\Configurable` |
| `Loggable` | interface | — | `log(Logging\LogLevel $level, Stringable|string $message, array $context = []): void`<br>`debug(Stringable|string $message, array $context = []): void`<br>`info(Stringable|string $message, array $context = []): void`<br>`notice(Stringable|string $message, array $context = []): void`<br>`warning(Stringable|string $message, array $context = []): void`<br>`error(Stringable|string $message, array $context = []): void`<br>`critical(Stringable|string $message, array $context = []): void`<br>`alert(Stringable|string $message, array $context = []): void`<br>`emergency(Stringable|string $message, array $context = []): void` |
| `LoggerConfigurable` | interface | — | `configure(array $config): void` |

**`Logging\Structural`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `ContextAware` | interface | — | `withContext(array $context): self`<br>`getContext(): array` |
| `FormatterAware` | interface | — | `setFormatter(ImmutableValue $formatter): Logging\Structural\FormatterAware`<br>`getFormatter(): ImmutableValue` |
| `HandlerAware` | interface | — | `addHandler(Logging\LogHandler $handler, ?Logging\LogLevel $level = null): Logging\Structural\HandlerAware`<br>`pushHandler(Logging\LogHandler $handler): Logging\Structural\HandlerAware`<br>`popHandler(): ?Logging\LogHandler`<br>`getHandlers(): array` |
| `LoggerAware` | interface | — | `setLogger(Logging\Logger $logger): void` |
| `ProcessorAware` | interface | — | `addProcessor(Logging\LogProcessor $processor): Logging\Structural\ProcessorAware`<br>`getProcessors(): array` |

**`Processor`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `ConfigurableProcessor` | interface | `Processor\Processor` | `configure(array $options): void` |
| `Pipeline` | interface | — | `addProcessor(Processor\Processor $processor): self`<br>`process(mixed $input): mixed` |
| `ProcessingResult` | interface | — | `addError(string $property, string $errorKey, string $message): void`<br>`setProcessedData(string $property, mixed $value): void`<br>`hasErrors(): bool`<br>`getErrors(): array`<br>`getProcessedData(): array`<br>`toArray(): array`<br>`clear(): void` |
| `Processor` | interface | — | `process(mixed $input): mixed` |
| `ProcessorBuilder` | interface | — | `build(string $context, string $name, array $processorConfig = []): Processor\Processor`<br>`buildPipeline(string $context, array $processorSpecs): Processor\Pipeline` |
| `ProcessorRegistry` | interface | — | `register(string $context, string $name, Processor\Processor $processor): self`<br>`get(string $context, string $name): Processor\Processor`<br>`getContextProcessors(string $context): DataStructure\Map` |
| `ProcessorValidator` | interface | — | `validate(Processor\Processor $processor, string $processorName, array $messages): ?array` |
| `ValidatableProcessor` | interface | `Processor\Processor` | `reset(): void`<br>`isValid(): bool`<br>`getErrorKey(): ?string` |

**`Processor\Attribute`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `BaseProcessorAttribute` | abstract class | `Processor\Attribute\CustomizableMessageAttribute`, `Processor\Attribute\ProcessableAttribute` | `__construct(array $processors, ?array $messages = null)`<br>`getProcessors(): array`<br>`getMessage(string $processorName): ?string` |
| `CustomizableMessageAttribute` | interface | — | `getMessage(string $validatorName): ?string` |
| `ProcessableAttribute` | interface | — | `getProcessors(): array` |

**`Resilience`** — new in 2.9.0

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `CallNotPermittedException` | interface | `\Throwable` | `?int $retryAfterSeconds { get; }` |
| `CircuitBreaker` | interface | — | `execute(callable $operation): mixed`<br>`isOpen(): bool` |

**`Sanitizer`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Sanitizer` | interface | — | `sanitize(mixed $input): mixed` |

**`Serializer`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Serializer` | interface | — | `serialize(mixed $input): string`<br>`deserialize(string $data): mixed` |

**`Transformer`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Transformer` | interface | — | `transform(mixed $object): mixed` |

**`Validator`**

| Type | Kind | Extends / implements | Members |
|---|---|---|---|
| `Validator` | interface | — | `validate(mixed $input): mixed` |

## 4. Compatibility Promise

- **Base.** The 2.8.6 dist downloaded from Packagist (GitHub zipball of
  reference `35935418…`). Its `.gitattributes` export-ignores `tests/`, so
  the 2.8.6 tests come from the git tag `v2.8.6`, the same commit.
- **Snapshot.** `tests/Compatibility/fixtures/2.8.6.json` was captured by
  `ApiSnapshot` with PHP 8.4.23 before any file changed: 54 types (53
  interfaces, 1 abstract class), 138 public and protected methods, no
  constants, no properties. Each record holds the kind, modifiers, parent,
  every interface the type satisfies, and its own constants, properties
  (type, hooks, asymmetric visibility, default) and methods (visibility,
  modifiers, return type, and each parameter's name, type, default,
  variadic, by-reference and promoted flags). Docblocks are not recorded.
- **Rule.** Every snapshot type must exist with an identical record. A
  removal, a change or an addition inside an inherited type fails, because a
  new method, parameter or parent interface breaks implementers. New types
  are the only additions allowed. `anyDifferenceInAnInheritedTypeIsReported`
  mutates a copy of the snapshot to prove that the comparison reports each
  kind of difference.
- **Docblock-only edit.** The inherited files lost only their `@author` (52)
  and `@license` (50) tags and the 54 blank docblock lines those left behind:
  156 lines removed, none added. With `T_DOC_COMMENT` tokens removed, the 54
  files are byte-identical to 2.8.6.
- **Regeneration.** `php tests/Compatibility/dump-api.php <src> <version> <reference>`.

## 5. Naming and Accessors

- Contracts are named after their role, without the `Interface` suffix
  (ADR-003). The three ADR-007 contracts were renamed before their first
  publication and keep their method signatures exactly; no suffixed alias
  exists, because nothing can depend on a name never published.
- A parameterless accessor is an interface property `{ get; }`. The only one
  in the new contracts is `CallNotPermittedException::$retryAfterSeconds`,
  and a `readonly` property satisfies it (§9).
- None of the ADR-007 contracts declares a `get*` method without
  parameters. `namespace()`, `defaults()` and `schema()` are parameterless
  named queries and keep the signatures every Configurator provider
  implements (ADR-003 §3).

## 6. Package Rules

- Interfaces only, including marker interfaces (ADR-001).
- `require` holds `php: ^8.4` and nothing else; interface properties need
  PHP 8.4 (ADR-002).
- Nothing asynchronous: no promise, future or event-loop type in any
  signature (ARFA 1.3 level 1).
- Cache and events between packages go through PSR-16 and PSR-14, not
  through this package.
- A new interface needs a second real consumer and its own ADR.

## 7. Test Suites

| Suite | Directory | Result (local) | What it proves |
|---|---|---|---|
| `inherited` | `tests/DI`, `DataStructure`, `Logging`, `Processor`, `Sanitizer`, `Serializer`, `Transformer`, `Validator` | 160 tests, 393 assertions | the 2.8.6 tests run unchanged on PHPUnit 11.5 |
| `unit` | `tests/Unit` | 21 tests, 65 assertions | a minimal implementation of each new interface behaves as §2 states |
| `compatibility` | `tests/Compatibility` | 3 tests, 17 assertions | every 2.8.6 type keeps its API; the comparison detects differences |
| `conformance` | `tests/Conformance` | 11 tests, 25 assertions | the Spec V4.0 rules of §10 over the whole `src/` tree |

## 8. Consumer Migration

| Consumer | Constraint | Uses | Change |
|---|---|---|---|
| ProcessorPipeline | `^2.8` | `Processor\Processor`, `Processor\ConfigurableProcessor` | none |
| Configurator 4.2 | `^4.1` → `^2.9` | `Environment`, `Configuration`, `Processor` | constraint and imports |
| Dotenv (bridge of ADR-007) | `^4.1` → `^2.9` | `Environment` | constraint and imports |
| CircuitBreaker 4.0.0 | `^2.9` | `Resilience` (implements) | none; drop the path repository once 2.9.0 is tagged |
| DataFlow, Observability | `^2.9` when they adopt it | `Resilience` | type the bridges against `CircuitBreaker` instead of `object` + `method_exists()` |

### 8.1 ProcessorPipeline

Nothing changes. `^2.8` accepts 2.9.0, and on PHP 8.4 Composer installs it;
`Processor\Processor` and `Processor\ConfigurableProcessor` are unchanged.
Its circuit breaker integration can now type against
`Resilience\CircuitBreaker`, which needs `^2.9`.

### 8.2 Configurator and Dotenv

1. `composer.json`: `"kariricode/contract": "^2.9"` instead of `"^4.1"`.
2. Imports:

   | Before (never published) | 2.9.0 |
   |---|---|
   | `Contract\Environment\EnvironmentResolverInterface` | `Contract\Environment\EnvironmentResolver` |
   | `Contract\Configuration\ValueProcessorInterface` | `Contract\Configuration\ValueProcessor` |
   | `Contract\Configuration\ConfigProviderInterface` | `Contract\Configuration\ConfigProvider` |
   | `Contract\Processor\ProcessorInterface` | `Contract\Processor\Processor` |

3. Configurator's deprecated local aliases (`Configurator\Contract\EnvironmentResolver`,
   `ValueProcessor`, `ConfigProvider`) share short names with the contracts,
   so they import them with an alias, for example
   `use KaririCode\Contract\Environment\EnvironmentResolver as EcosystemEnvironmentResolver;`
   and `interface EnvironmentResolver extends EcosystemEnvironmentResolver {}`.
   Code implementing the local aliases keeps working.
4. `Integration\ConfigurationProcessor` implements `Processor\Processor`
   (`process(mixed $input): mixed`), so `ProcessorRegistry::register()`
   accepts it and the configuration runs inside a pipeline; the
   `ProcessorInterface` stub goes away.
5. Method bodies stay: every signature is identical.
6. Documentation: Configurator ADR-007 (version and names) and
   SPEC-CONFIGURATOR §2.1 and §12.3.
7. Until 2.9.0 is tagged, resolve it from a path repository:
   `"repositories": [{"type": "path", "url": "../kariricode-contract"}]`.

### 8.3 CircuitBreaker

It already requires `^2.9`. Once 2.9.0 is tagged, remove the path repository
and the README note that asks to apply the patch; its
`kariricode-contract-resilience/` copy becomes redundant. The two files are
the literal copy (SHA-256 of `CircuitBreaker.php`
`1981562bad954f20516b02517b8bc56ea5514686a2d6dd748658cd28d76674d1`, of
`CallNotPermittedException.php`
`44dac7fdefc4996cff9a81f64e3a49c7e2de17859b7fc78e574df4ac5c92892e`).

## 9. Errata E-013 (proposed)

**Problem.** Spec V4.0 allows `get` property hooks in `final readonly`
DPO/VO classes. PHP rejects that at compile time. Measured on PHP 8.4.23
(CLI, static build), 2026-09-30, and reproduced by
`CallNotPermittedExceptionTest::hooksOnReadonlyPropertiesDoNotCompilePerErrataE013`:

| Declaration | Result |
|---|---|
| `final readonly class` with a virtual `get`-hooked property | `Fatal error: Hooked properties cannot be readonly` |
| `final class` (not readonly) with the same hook | compiles |

CircuitBreaker 4.0.0 (SPEC-001 §9) also measured a promoted hooked property
in a readonly class, with the same fatal error. The error is raised while
compiling the file, so no baseline or suppression can make such a class
usable.

**Proposed resolution.** Readonly types (DPO, VO, events, exceptions with
readonly state) expose derived values through methods. Property hooks are
allowed only in non-readonly classes, such as entities.

**Conformance check.** For every readonly type or property,
`ReflectionProperty::hasHooks()` is false
(`ArfaConformanceTest::readonlyTypesDeclareNoPropertyHooksPerErrataE013`).

**Related finding: a readonly property satisfies `{ get; }`.** The interface
property `CallNotPermittedException::$retryAfterSeconds` is abstract with a
`get` hook; a class that implements it with a plain `public readonly`
property has no hook, reads the value and throws `Error` on reassignment
(`CallNotPermittedExceptionTest::aReadonlyPropertySatisfiesTheGetRequirementWithoutAHook`,
`theDelayCannotBeReassigned`). Contracts can therefore keep property
requirements while implementations stay immutable.

**Numbering.** E-013 is the number CircuitBreaker 4.0.0 used, the next free
one after Spec V4.0's E-001 to E-012. Spec V4.3.3 already uses E-013 for
"DPO jargon" (resolved in V4.1) and registers this same rule as E-040
("Property hooks on readonly properties do not compile"). Read E-013 here
as E-040 when V4.3.3 is adopted; QD-V40-COMPLIANCE §6 tracks the
renumbering.

## 10. ARFA 1.3 and Spec V4.0 Mapping

| Rule | How this package meets it | Verified by |
|---|---|---|
| P1 Immutable State Transformation | no mutable state in contracts; the one property is read-only | conformance (E-013); unit tests |
| P2 Reactive Flow Composition | not required at level 1; contracts are synchronous | code review (§6) |
| P3 Adaptive Context Awareness | not required at level 1; `CallNotPermittedException` lets callers choose a fallback | `CircuitBreakerTest` |
| P4 Protocol Agnosticism | no transport data: a rejection carries seconds, not an HTTP status | `theContractAddsOnlyTheDelayToThrowable` |
| P5 Continuous Observability | belongs to implementations; interfaces emit nothing | not applicable |
| Interfaces only (or marker) | 58 interfaces; 1 inherited class listed | `onlyInterfacesAreDeclared` |
| No `Interface` suffix, no `Manager` / `Helper` / `Util` | new names comply; 2 inherited listed | `namesCarryNoInterfaceSuffixNorVagueNoun` |
| No getter without parameters | property `{ get; }` instead; 24 inherited listed | `noGetterWithoutParameters` |
| E-001 no `toArray()` / `jsonSerialize()` | none new; 1 inherited listed | `noTypeSerializesItself` |
| One type per file, named after it | new files comply; 1 inherited listed | `eachFileDeclaresOneTypeNamedAfterIt` |
| Typed class constants | no constant is declared | `classConstantsAreTyped` |
| E-013 (proposed) no hooks in readonly types | no readonly type declares hooks | `readonlyTypesDeclareNoPropertyHooksPerErrataE013` |
| No `@package`, `@author`, `@license` in docblocks | removed from inherited files; absent from new ones | `docblocksCarryNoAuthorshipTags` |
| No runtime dependency | `require` is `php: ^8.4` only | `theRuntimeRequiresNothingButPhp` |
| Additive minor release | 2.8.6 API unchanged | `BackwardCompatibilityTest` |
| E-003 backed enums | no enum is declared | not applicable |
| E-006 measured claims only | README metrics measured; QD stays PENDING | `docs/quality/QD-V40-COMPLIANCE.md` |
| Static analysis | PHPStan max and Psalm 2: 0 errors in new code; inherited findings in baselines | `staticAnalysisBaselinesCoverInheritedFilesOnly` |

## 11. Inherited Deviations (3.0.0)

Frozen in 2.x because fixing them breaks implementers; named in
`ArfaConformanceTest::INHERITED_DEVIATIONS`, which fails when one disappears
without the list being updated.

| Rule | Deviations |
|---|---|
| Interfaces only | `Processor\Attribute\BaseProcessorAttribute` (abstract class) |
| Names | `Logging\LoggerExtendedInterface`, `Logging\LoggingManager` |
| Getters without parameters (24) | `DI\Definition::getConcrete`, `getParameters`, `getTags`; `DataStructure\Behavioral\IterableCollection::getIterator`; `DataStructure\Structural\BPlusTreeCollection::getMaximum`, `getMinimum`, `getOrder`; `DataStructure\Structural\Collection::getItems`; `Logging\Behavioral\Configurable::getConfig`; `Logging\LogLevel::getColor`, `getLevel`, `getValue`; `Logging\Logger::getName`; `Logging\LoggerExtendedInterface::getName`; `Logging\LoggingManager::getName`; `Logging\Structural\ContextAware::getContext`; `FormatterAware::getFormatter`; `HandlerAware::getHandlers`; `ProcessorAware::getProcessors`; `Processor\Attribute\BaseProcessorAttribute::getProcessors`; `Processor\Attribute\ProcessableAttribute::getProcessors`; `Processor\ProcessingResult::getErrors`, `getProcessedData`; `Processor\ValidatableProcessor::getErrorKey` |
| E-001 | `Processor\ProcessingResult::toArray` |
| One type per file, named after it | `Logging/LoggerExtended.php` declares `LoggerExtendedInterface` |

Other findings, outside the automated rules:

- **Not autoloadable.** PSR-4 maps `Logging\LoggerExtendedInterface` to
  `Logging/LoggerExtendedInterface.php`, which does not exist; the type
  cannot be autoloaded since 2.8.6.
- **Named-argument break.** `BaseProcessorAttribute::getMessage(string $processorName)`
  renames the parameter of `CustomizableMessageAttribute::getMessage(string $validatorName)`,
  so a call with the interface's named argument fails at runtime (Psalm
  `ParamNameMismatch`).
- **Dangling docblock type.** `ProcessorRegistry::getContextProcessors()`
  documents `@return HashMap`, which does not exist.
- **Unused import.** `Logging\Behavioral\Loggable` imports
  `Psr\Log\LoggerInterface` without using it; `psr/log` is not required.
- **Self-serialization.** `DataStructure\Structural\Serializable` declares
  `serialize()` and `deserialize()`, the pattern E-001 forbids on domain
  objects; the name-based rule does not catch it.
- **Static analysis.** Baselines hold 46 PHPStan findings in 17 files (38
  missing iterable value types, 2 non-generic types used as generics, 2
  return and 1 parameter docblock mismatches, 1 missing generic, 1 return
  type, 1 unknown class) and 10 Psalm findings in 6 files.
- **Coding standard.** Four inherited files (two in `src/`, two in
  `tests/`) deviate from the PER-CS 2.0 set; the fixer skips inherited code.

## 12. Limitations

- The compatibility test compares what Reflection sees. Docblock contracts
  (`@template`, array shapes) and behavioural promises are not compared.
- Inherited types are frozen as a whole, so even an addition that would be
  compatible for a class needs a new snapshot and an ADR.
- The conformance rules are structural: a parameterless query without the
  `get` prefix is not detected as an accessor.
- The 160 inherited tests exercise PHPUnit mocks of the interfaces: they
  prove the interfaces can be implemented and called, not any behaviour.
- Type strings in the snapshot come from `ReflectionType::__toString()`; a
  PHP release that changed their format would require regenerating the
  fixture from the 2.8.6 dist.
- Line coverage was not measured; the only executable code in `src/` is the
  inherited `BaseProcessorAttribute`.

## 13. References

- PHP RFC "Property Hooks" (PHP 8.4) — interface properties; hooks and
  readonly.
- IETF RFC 9110, *HTTP Semantics* (2022), §10.2.3 (`Retry-After`) and
  §15.6.4 (503).
- Semantic Versioning 2.0.0; Composer documentation, "Versions and
  constraints".
- PHP-FIG: PSR-4 (autoloading), PSR-14, PSR-16; "PSR Naming Conventions".
- Nygard, M. T. (2018). *Release It!* (2nd ed.). Pragmatic Bookshelf —
  Circuit Breaker.
- KaririCode Configurator, ADR-007; `kariricode/circuit-breaker` 4.0.0,
  ADR-003 and SPEC-001; KaririCode Code Quality Directive V4.0 and
  Spec V4.3.3 errata E-040.
