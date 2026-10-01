# SPEC-001: kariricode/contract 2.10.1

| Field | Value |
|---|---|
| Status | Accepted (content); quality gate PENDING — see QD-V40-COMPLIANCE |
| Date | 2026-09-30 |
| Component | kariricode/contract 2.10.1 |
| Authors | Walmir Silva <walmir.silva@kariricode.org> |
| Standards | ARFA 1.3 (level 1); KaririCode Code Quality Directive V4.0 |
| Error codes | none: the package declares interfaces and throws nothing |
| PHP | ^8.4 |
| Base | 2.8.6 Packagist dist, reference `35935418be93009a1ce389460e6fbf87353f0dd7`; 2.9.0 release tarball, SHA-256 `86b11923ea913650a8c4a3e877be4c734057ace9b40d0e434ba985f1b2946217`; 2.10.0 release tarball, SHA-256 `7197a54377da3aaee7d3c0f82f01ff0686e312cf08a8bd099ac6d9c39bc6cbb4` |

## 1. Scope

`kariricode/contract` holds the interfaces that KaririCode packages share,
so that one package implements a contract and others type against it
without depending on each other (ADR-001). 2.9.0 added five interfaces to
the 2.8.6 package (ADR-002, ADR-003); 2.10.0 adds five resilience ports
(ADR-004); 2.10.1 changes documentation and tests only. No type of an
earlier release changed.

## 2. API Added Since 2.8.6

### 2.1 In 2.9.0

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

### 2.2 In 2.10.0 (ADR-004)

```php
namespace KaririCode\Contract\Resilience;

interface RateLimiter
{
    public function attempt(string $key, int $cost = 1): RateLimitDecision;

    public function peek(string $key): RateLimitDecision;

    /** @throws \RuntimeException when the state store cannot be reached */
    public function reset(string $key): void;
}

interface RateLimitDecision
{
    public bool $allowed { get; }
    public int $limit { get; }
    public int $remaining { get; }
    public int $retryAfter { get; }
    public int $resetAfter { get; }
}

interface CallPermit {}

interface ManualCircuitBreaker extends CircuitBreaker
{
    public function tryAcquirePermit(): ?CallPermit;

    /** @throws CallNotPermittedException when the call is not admitted */
    public function acquirePermit(): CallPermit;

    public function recordSuccess(CallPermit $permit): void;

    public function recordFailure(CallPermit $permit, \Throwable $error): void;

    public function releasePermit(CallPermit $permit): void;
}

interface RetryPolicy
{
    public function shouldRetry(\Throwable $error, int $attempt): bool;

    public function delayMilliseconds(\Throwable $error, int $attempt): int;
}
```

| Member | Semantics | Verified by |
|---|---|---|
| `RateLimiter::attempt()` | consumes `$cost` units when allowed, nothing when denied; a denial is a value; `$cost` must be between 1 and the limiter's capacity, other costs are rejected with an exception | `RateLimiterTest` |
| `RateLimiter::peek()` | answers for one unit, consumes nothing | `peekAnswersForOneUnitWithoutConsuming` |
| `RateLimiter::reset()` | restores the key's full quota; throws when the store is unreachable | `resetRestoresTheFullQuota` |
| `RateLimitDecision` | `0 ≤ remaining ≤ limit`; `retryAfter` is 0 when allowed and ≥ 1 when denied; whole seconds, rounded up | `aDenialConsumesNothingAndSaysWhenToRetry`, `aReadonlyClassSatisfiesTheDecisionWithoutHooks` |
| `ManualCircuitBreaker::tryAcquirePermit()` | a permit, or null when the call is not admitted (open, half-open trials in use, state unknown under fail-closed); never throws for a rejection | `tryAcquirePermitReturnsNullInsteadOfThrowingWhenOpen` |
| `ManualCircuitBreaker::acquirePermit()` | a permit, or `CallNotPermittedException` carrying `retryAfterSeconds` | `acquirePermitReportsTheRejectionWithItsDelay` |
| `recordSuccess()`, `recordFailure()` | settle the permit once, on the breaker that issued it; the breaker decides whether an error counts, so ignored errors never open the circuit; the implementation measures the duration from the permit | `theBreakerDecidesWhichFailuresCount`, `aPermitSettlesExactlyOnce`, `aPermitIsSettledOnlyByTheBreakerThatIssuedIt` |
| `releasePermit()` | the admitted call did not run: nothing is recorded, a half-open trial slot is freed | `aReleasedPermitRecordsNothing` |
| `RetryPolicy::shouldRetry()` | `$attempt` is the number of calls already made, all failed (1 after the first failure); the policy classifies the error | `attemptCountsTheCallsAlreadyMade`, `thePolicyClassifiesTheError` |
| `RetryPolicy::delayMilliseconds()` | asked only after `shouldRetry()` returned true; milliseconds, ≥ 0; may honour a wait the error carries; jitter expected | `delaysStayInsideTheFullJitterEnvelope`, `theDelayHonoursTheWaitAnErrorCarries` |

Units: the limiter's waits (`retryAfter`, `resetAfter`) and the breaker's
hint (`retryAfterSeconds`) are whole seconds; retry delays are milliseconds.

## 3. Inventory

64 types in 17 namespaces: 63 interfaces and one inherited abstract class.
157 public methods (138 from 2.8.6, 9 from 2.9.0, 10 from 2.10.0) and six
interface properties (one from 2.9.0, five from 2.10.0).

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
| `Resilience` | `CallNotPermittedException` (2.9.0), `CallPermit` (2.10.0), `CircuitBreaker` (2.9.0), `ManualCircuitBreaker` (2.10.0), `RateLimitDecision` (2.10.0), `RateLimiter` (2.10.0), `RetryPolicy` (2.10.0) | 2.9.0, 2.10.0 |
| `Sanitizer` | `Sanitizer` | ≤ 2.8.6 |
| `Serializer` | `Serializer` | ≤ 2.8.6 |
| `Transformer` | `Transformer` | ≤ 2.8.6 |
| `Validator` | `Validator` | ≤ 2.8.6 |

### 3.2 Types and members

Generated by Reflection from `src/`; `Extends` lists direct parents only.

**`(root)`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `ImmutableValue` | ≤ 2.8.6 | interface | — | — (marker) |

**`Configuration`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `ConfigProvider` | 2.9.0 | interface | — | `namespace(): string`<br>`defaults(): array`<br>`schema(): array` |
| `ValueProcessor` | 2.9.0 | interface | — | `supports(mixed $value): bool`<br>`process(mixed $value): mixed` |

**`DI`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Compiler` | ≤ 2.8.6 | interface | — | `compile(DI\Container $container): DI\Container`<br>`writeToFile(string $path): void`<br>`loadFromFile(string $path): DI\Container` |
| `Container` | ≤ 2.8.6 | interface | — | `set(string $id, callable $service): void`<br>`get(string $id): mixed`<br>`has(string $id): bool`<br>`resolve(string $abstract): object`<br>`bind(string $abstract, mixed $concrete, array $parameters = []): void`<br>`singleton(string $abstract, mixed $concrete, array $parameters = []): void`<br>`factory(string $abstract, mixed $concrete, array $parameters = []): void`<br>`tag(string $tag, array $abstracts): void`<br>`tagged(string $tag): array`<br>`call(callable|string $callback, array $parameters = []): mixed`<br>`extend(string $abstract, Closure $extender): void`<br>`alias(string $abstract, string $alias): void` |
| `Definition` | ≤ 2.8.6 | interface | — | `getConcrete(): mixed`<br>`getParameters(): array`<br>`getTags(): array`<br>`isSingleton(): bool`<br>`isFactory(): bool` |
| `Provider` | ≤ 2.8.6 | interface | — | `register(DI\Container $container): void`<br>`boot(DI\Container $container): void` |

**`DataStructure`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Deque` | ≤ 2.8.6 | interface | `DataStructure\Queue` | `addFirst(mixed $element): void`<br>`removeLast(): mixed`<br>`peekLast(): mixed` |
| `Heap` | ≤ 2.8.6 | interface | — | `add(mixed $element): void`<br>`insert(int $index, mixed $element): void`<br>`poll(): mixed`<br>`remove(mixed $element): bool`<br>`peek(): mixed` |
| `Map` | ≤ 2.8.6 | interface | `DataStructure\Behavioral\Countable`, `DataStructure\Behavioral\IterableCollection` | `put(mixed $key, mixed $value): void`<br>`get(mixed $key): mixed`<br>`remove(mixed $key): bool`<br>`containsKey(mixed $key): bool`<br>`keys(): array`<br>`values(): array`<br>`clear(): void` |
| `Queue` | ≤ 2.8.6 | interface | `DataStructure\Behavioral\Modifiable` | `enqueue(mixed $element): void`<br>`dequeue(): mixed`<br>`peek(): mixed` |
| `Set` | ≤ 2.8.6 | interface | `DataStructure\Behavioral\Modifiable`, `DataStructure\Behavioral\Searchable`, `\Traversable` | `union(DataStructure\Set $other): DataStructure\Set`<br>`intersection(DataStructure\Set $other): DataStructure\Set`<br>`difference(DataStructure\Set $other): DataStructure\Set` |
| `Stack` | ≤ 2.8.6 | interface | — | `push(mixed $element): void`<br>`pop(): mixed`<br>`peek(): mixed` |
| `Tree` | ≤ 2.8.6 | interface | `DataStructure\Behavioral\Modifiable`, `DataStructure\Behavioral\Searchable` | `insert(int $index, mixed $value): void` |

**`DataStructure\Behavioral`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Comparable` | ≤ 2.8.6 | interface | — | `compareTo(mixed $other): int` |
| `Countable` | ≤ 2.8.6 | interface | — | `size(): int` |
| `Indexable` | ≤ 2.8.6 | interface | — | `get(mixed $index): mixed`<br>`set(mixed $index, mixed $element): void` |
| `IterableCollection` | ≤ 2.8.6 | interface | `\Traversable` | `getIterator(): Iterator` |
| `Iterator` | ≤ 2.8.6 | interface | — | `next(): mixed`<br>`hasNext(): bool` |
| `Modifiable` | ≤ 2.8.6 | interface | — | `add(mixed $element): void`<br>`remove(mixed $element): bool`<br>`clear(): void` |
| `Searchable` | ≤ 2.8.6 | interface | — | `contains(mixed $element): bool`<br>`find(mixed $element): mixed` |
| `Sortable` | ≤ 2.8.6 | interface | — | `sort(): void` |

**`DataStructure\Structural`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `BPlusTreeCollection` | ≤ 2.8.6 | interface | `DataStructure\Behavioral\Sortable`, `DataStructure\Structural\Collection`, `DataStructure\Tree` | `getOrder(): int`<br>`rangeSearch(mixed $start, mixed $end): array`<br>`getMinimum(): mixed`<br>`getMaximum(): mixed`<br>`balance(): void` |
| `Collection` | ≤ 2.8.6 | interface | `DataStructure\Behavioral\Countable`, `DataStructure\Behavioral\Indexable`, `DataStructure\Behavioral\Modifiable`, `DataStructure\Behavioral\Searchable` | `addAll(DataStructure\Structural\Collection $collection): void`<br>`getItems(): array` |
| `Serializable` | ≤ 2.8.6 | interface | — | `serialize(): mixed`<br>`deserialize(mixed $data): void` |

**`Environment`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `EnvironmentResolver` | 2.9.0 | interface | — | `get(string $name): ?string`<br>`has(string $name): bool` |

**`Logging`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `LogFormatter` | ≤ 2.8.6 | interface | — | `format(ImmutableValue $record): string`<br>`formatBatch(array $records): string` |
| `LogHandler` | ≤ 2.8.6 | interface | — | `handle(ImmutableValue $record): void`<br>`isHandling(ImmutableValue $record): bool` |
| `LogLevel` | ≤ 2.8.6 | interface | — | `getLevel(): string`<br>`getColor(): string`<br>`getValue(): int` |
| `LogProcessor` | ≤ 2.8.6 | interface | — | `process(ImmutableValue $record): ImmutableValue` |
| `LogRotator` | ≤ 2.8.6 | interface | — | `shouldRotate(string $filePath): bool`<br>`rotate(string $filename): void` |
| `Logger` | ≤ 2.8.6 | interface | `Logging\Behavioral\Loggable` | `getName(): string` |
| `LoggerExtendedInterface` | ≤ 2.8.6 | interface | `Logging\Logger` | `addHandler(Logging\Structural\HandlerAware $handler): self`<br>`addProcessor(Logging\Structural\ProcessorAware $processor): self`<br>`setFormatter(Logging\Structural\FormatterAware $formatter): self`<br>`getName(): string` |
| `LoggingManager` | ≤ 2.8.6 | interface | `Logging\Behavioral\Configurable`, `Logging\Behavioral\Loggable`, `Logging\Structural\ContextAware`, `Logging\Structural\FormatterAware`, `Logging\Structural\HandlerAware`, `Logging\Structural\ProcessorAware` | `getName(): string` |

**`Logging\Behavioral`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Configurable` | ≤ 2.8.6 | interface | — | `getConfig(): ImmutableValue`<br>`setConfig(ImmutableValue $config): Logging\Behavioral\Configurable` |
| `Loggable` | ≤ 2.8.6 | interface | — | `log(Logging\LogLevel $level, Stringable|string $message, array $context = []): void`<br>`debug(Stringable|string $message, array $context = []): void`<br>`info(Stringable|string $message, array $context = []): void`<br>`notice(Stringable|string $message, array $context = []): void`<br>`warning(Stringable|string $message, array $context = []): void`<br>`error(Stringable|string $message, array $context = []): void`<br>`critical(Stringable|string $message, array $context = []): void`<br>`alert(Stringable|string $message, array $context = []): void`<br>`emergency(Stringable|string $message, array $context = []): void` |
| `LoggerConfigurable` | ≤ 2.8.6 | interface | — | `configure(array $config): void` |

**`Logging\Structural`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `ContextAware` | ≤ 2.8.6 | interface | — | `withContext(array $context): self`<br>`getContext(): array` |
| `FormatterAware` | ≤ 2.8.6 | interface | — | `setFormatter(ImmutableValue $formatter): Logging\Structural\FormatterAware`<br>`getFormatter(): ImmutableValue` |
| `HandlerAware` | ≤ 2.8.6 | interface | — | `addHandler(Logging\LogHandler $handler, ?Logging\LogLevel $level = null): Logging\Structural\HandlerAware`<br>`pushHandler(Logging\LogHandler $handler): Logging\Structural\HandlerAware`<br>`popHandler(): ?Logging\LogHandler`<br>`getHandlers(): array` |
| `LoggerAware` | ≤ 2.8.6 | interface | — | `setLogger(Logging\Logger $logger): void` |
| `ProcessorAware` | ≤ 2.8.6 | interface | — | `addProcessor(Logging\LogProcessor $processor): Logging\Structural\ProcessorAware`<br>`getProcessors(): array` |

**`Processor`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `ConfigurableProcessor` | ≤ 2.8.6 | interface | `Processor\Processor` | `configure(array $options): void` |
| `Pipeline` | ≤ 2.8.6 | interface | — | `addProcessor(Processor\Processor $processor): self`<br>`process(mixed $input): mixed` |
| `ProcessingResult` | ≤ 2.8.6 | interface | — | `addError(string $property, string $errorKey, string $message): void`<br>`setProcessedData(string $property, mixed $value): void`<br>`hasErrors(): bool`<br>`getErrors(): array`<br>`getProcessedData(): array`<br>`toArray(): array`<br>`clear(): void` |
| `Processor` | ≤ 2.8.6 | interface | — | `process(mixed $input): mixed` |
| `ProcessorBuilder` | ≤ 2.8.6 | interface | — | `build(string $context, string $name, array $processorConfig = []): Processor\Processor`<br>`buildPipeline(string $context, array $processorSpecs): Processor\Pipeline` |
| `ProcessorRegistry` | ≤ 2.8.6 | interface | — | `register(string $context, string $name, Processor\Processor $processor): self`<br>`get(string $context, string $name): Processor\Processor`<br>`getContextProcessors(string $context): DataStructure\Map` |
| `ProcessorValidator` | ≤ 2.8.6 | interface | — | `validate(Processor\Processor $processor, string $processorName, array $messages): ?array` |
| `ValidatableProcessor` | ≤ 2.8.6 | interface | `Processor\Processor` | `reset(): void`<br>`isValid(): bool`<br>`getErrorKey(): ?string` |

**`Processor\Attribute`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `BaseProcessorAttribute` | ≤ 2.8.6 | abstract class | `Processor\Attribute\CustomizableMessageAttribute`, `Processor\Attribute\ProcessableAttribute` | `__construct(array $processors, ?array $messages = null)`<br>`getProcessors(): array`<br>`getMessage(string $processorName): ?string` |
| `CustomizableMessageAttribute` | ≤ 2.8.6 | interface | — | `getMessage(string $validatorName): ?string` |
| `ProcessableAttribute` | ≤ 2.8.6 | interface | — | `getProcessors(): array` |

**`Resilience`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `CallNotPermittedException` | 2.9.0 | interface | `\Throwable` | `?int $retryAfterSeconds { get; }` |
| `CallPermit` | 2.10.0 | interface | — | — (marker) |
| `CircuitBreaker` | 2.9.0 | interface | — | `execute(callable $operation): mixed`<br>`isOpen(): bool` |
| `ManualCircuitBreaker` | 2.10.0 | interface | `Resilience\CircuitBreaker` | `tryAcquirePermit(): ?Resilience\CallPermit`<br>`acquirePermit(): Resilience\CallPermit`<br>`recordSuccess(Resilience\CallPermit $permit): void`<br>`recordFailure(Resilience\CallPermit $permit, Throwable $error): void`<br>`releasePermit(Resilience\CallPermit $permit): void` |
| `RateLimitDecision` | 2.10.0 | interface | — | `bool $allowed { get; }`<br>`int $limit { get; }`<br>`int $remaining { get; }`<br>`int $retryAfter { get; }`<br>`int $resetAfter { get; }` |
| `RateLimiter` | 2.10.0 | interface | — | `attempt(string $key, int $cost = 1): Resilience\RateLimitDecision`<br>`peek(string $key): Resilience\RateLimitDecision`<br>`reset(string $key): void` |
| `RetryPolicy` | 2.10.0 | interface | — | `shouldRetry(Throwable $error, int $attempt): bool`<br>`delayMilliseconds(Throwable $error, int $attempt): int` |

**`Sanitizer`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Sanitizer` | ≤ 2.8.6 | interface | — | `sanitize(mixed $input): mixed` |

**`Serializer`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Serializer` | ≤ 2.8.6 | interface | — | `serialize(mixed $input): string`<br>`deserialize(string $data): mixed` |

**`Transformer`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Transformer` | ≤ 2.8.6 | interface | — | `transform(mixed $object): mixed` |

**`Validator`**

| Type | Since | Kind | Extends | Members |
|---|---|---|---|---|
| `Validator` | ≤ 2.8.6 | interface | — | `validate(mixed $input): mixed` |

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
- **2.9.0 snapshot.** `tests/Compatibility/fixtures/2.9.0.json` was
  captured the same way from the 2.9.0 release tarball (SHA-256
  `86b11923…`) before 2.10.0 changed anything: 59 types.
- **2.10.0 snapshot.** `tests/Compatibility/fixtures/2.10.0.json`, from the
  2.10.0 release tarball (SHA-256 `7197a543…`): 64 types.
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
- A parameterless accessor is an interface property `{ get; }`:
  `CallNotPermittedException::$retryAfterSeconds` (2.9.0) and the five
  properties of `RateLimitDecision` (2.10.0). A `readonly` property satisfies
  them (§9).
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
| `unit` | `tests/Unit` | 39 tests, 3,337 assertions | a minimal implementation of each contract added since 2.9.0 behaves as §2 states; most assertions come from sampling retry delays |
| `compatibility` | `tests/Compatibility` | 7 tests, 39 assertions | every 2.8.6, 2.9.0 and 2.10.0 type keeps its API; the comparison detects differences |
| `conformance` | `tests/Conformance` | 14 tests, 41 assertions | the rules of §10 over the whole `src/` tree; for the 2.10.0 ports, documentation criteria C1–C5 and synchronous signatures; ARFA and ASVS levels (G4) |

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

### 8.4 Resilience ports (2.10.0)

| Package | Today | With 2.10.0 | Change |
|---|---|---|---|
| circuit-breaker 4.0.0 | implements `CircuitBreaker`; manual permits only on `Core\CircuitBreaker` | implements `ManualCircuitBreaker` | add `tryAcquirePermit()` (null where `acquirePermit()` throws `CircuitOpenException`); add `releasePermit()` (frees a half-open trial of the same epoch, no-op otherwise); `Core\CallPermit implements Contract\Resilience\CallPermit`; type `recordSuccess()` / `recordFailure()` against the contract's `CallPermit`, keeping the 7130 check; require `^2.10`; drop `kariricode-contract-resilience/` |
| rate-limiter 4.0.0 | autoloads `KaririCode\Contract\Resilience\` from `contract-proposal/` | implements `RateLimiter` from the contract | delete `contract-proposal/` and its autoload entry; require `^2.10` (its README says `^2.9`, which lacks these types) |
| Observability | `CircuitBreakingExporter(?object)` + `execute()`; `RateLimitedLogger(?object)`; retry loop in `AsyncBatchExporter` | `?CircuitBreaker`, `?RateLimiter`, optional `RetryPolicy` | type the parameters; `->attempt($key)->allowed`; keep the built-in fallbacks for null; build the limiter with rate-limiter 4.0.0 (`TokenBucket(maxTokens, refillRate)` no longer exists); take the default `RetryPolicy` from async-helper instead of the own loop |
| DataFlow | `RateLimiterBridge(object)` + `method_exists('attempt')`, `isAllowed()` / `getRetryAfter()` or a bool; `CircuitBreakerBridge(object)`; `Resilience\RetryStrategy` | `RateLimiter`; `ManualCircuitBreaker` for asynchronous stages; `RetryPolicy` | read `$decision->allowed` and `$decision->retryAfter`; asynchronous stages use `acquirePermit()` and map `CallNotPermittedException` (7100–7102); replace `RetryStrategy`'s loop with a `RetryPolicy` (async-helper's adapter) |
| Authenticator | bridge `check($bucket, $max, $window)`, `recordFailure($bucket)` | `RateLimiter` | one limiter per policy, built with `Quota::of($max, $window)` (e.g. `SlidingWindowLog`); `check` → `peek($bucket)->allowed`; `recordFailure` → `attempt($bucket)`; optionally `reset($bucket)` on success |
| async-helper 4.0 | `Contract\BackoffStrategy`, `Contract\RetryStrategy`, `Retry\JitteredBackoff` (full jitter) | implements `RetryPolicy` | adapter that classifies the error and takes the delay from a `BackoffStrategy`; its own interfaces need a Spec V4.3.3 pass (parameterless getters, `@package` tags) |
| async 4.0 | `Integration\CircuitBreakerBridge(object $circuitBreaker, …)` returning a promise | `ManualCircuitBreaker` | acquire a permit, settle it when the promise settles; `releasePermit()` when the operation never starts |
| processor-pipeline | no resilience decorators | `ManualCircuitBreaker`, `RateLimiter`, `RetryPolicy` | decorators typed against the ports; `"kariricode/contract": "^2.10"` |

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
| Static analysis | PHPStan max and Psalm 2: 0 errors in new code; inherited findings in baselines. Psalm 1 (strict, no baseline) over the source added since 2.9.0: 0 errors | `staticAnalysisBaselinesCoverInheritedFilesOnly`, `composer psalm-strict` |
| Documentation criteria C1–C5 (Spec V4.3.3 §17) on the 2.10.0 ports | preconditions and thread-safety (C2), side effects (C3), example (C4), references (C5) | `theResiliencePortsDocumentTheFiveCriteria` |
| Level 1: nothing asynchronous | no promise, fiber or generator in the ports' signatures | `theResiliencePortsAreSynchronousInterfaces` |
| Additive over 2.9.0 and 2.10.0 | their APIs unchanged | `BackwardCompatibilityTest` (2.9.0 and 2.10.0 snapshots) |
| Levels declared (G4) | `extra.arfa.level` and `extra.asvs.level` | `composerDeclaresTheArfaAndAsvsLevels` |

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
- `RetryPolicy` has no implementation yet (planned as an adapter in
  async-helper, ADR-004 amendment); its semantics rest on the test fixture
  `FullJitterBackoff`. `ManualCircuitBreaker` and `CallPermit` need
  the circuit-breaker changes of §8.4 before a library implements them.
- The line numbers of the libraries' own APIs could not be checked: their
  release tarballs were not available when 2.10.0 was written (ADR-004 cites
  their design records).

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
- Resilience4j documentation: "CircuitBreaker", "RateLimiter", "Retry".
- Brooker, M. (2015). "Exponential Backoff And Jitter". AWS Architecture Blog.
- Fowler, M. (2014). *CircuitBreaker*. martinfowler.com/bliki/CircuitBreaker.html
- `kariricode/rate-limiter` 4.0.0, SPEC-001 §3; ADR-004 of this package.
- Spec V4.3.3 §17 (documentation criteria C1–C5) and §29.3 (gate G4).
- ADR-005 (proposed): shared cross-cutting contracts for 2.11.0.
