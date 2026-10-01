# ADR-004: Resilience ports

- **Status:** Accepted; amended 2026-10-01 (see the end)
- **Date:** 2026-09-30
- **Component:** kariricode/contract 2.10.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

Spec V4.0 §29 routes every coupling between components through
`kariricode/contract`. Until 2.9.0 the contract had nothing for resilience,
so each consumer invented its own duck-typed link:

| Consumer | Link today | Problem |
|---|---|---|
| Observability `CircuitBreakingExporter` | `?object` + `execute(callable)`; fails open without it | untyped; a missing method silently disables protection |
| Observability `RateLimitedLogger` | `?object`, built as `new RateLimiter(new TokenBucket(maxTokens, refillRate))` | a 3.x constructor that rate-limiter 4.0.0 no longer has |
| DataFlow `RateLimiterBridge` | `object` + `method_exists('attempt')`; reads `isAllowed()` / `getRetryAfter()`, or a bool | two result shapes guessed at runtime |
| Authenticator | own bridge: `check($bucket, $max, $window)`, `recordFailure($bucket)` | failure counting kept outside the limiter |

Two libraries were rewritten on 2026-09-30 against Spec V4.0 and implement
typed contracts they ship next to themselves:

- `kariricode/circuit-breaker` 4.0.0 implements `Resilience\CircuitBreaker`
  (`execute`, `isOpen`) and `CallNotPermittedException`, released in
  contract 2.9.0. Its own class also has a manual permit protocol
  (`acquirePermit(): CallPermit`, `recordSuccess(CallPermit)`,
  `recordFailure(CallPermit, Throwable)`) for callers that finish later.
- `kariricode/rate-limiter` 4.0.0 implements `Resilience\RateLimiter`
  (`attempt`, `peek`, `reset`) and `RateLimitDecision` (get-only `allowed`,
  `limit`, `remaining`, `retryAfter`, `resetAfter`), shipped in its
  `contract-proposal/` directory until a contract release carries them.

No retry library exists. Two packages run their own backoff loop with
different formulas: DataFlow `Resilience\RetryStrategy` (base ×
multiplier^(k−1) ± 25 %, capped, list of retryable classes) and
Observability `AsyncBatchExporter` (base × 2^(k−1) ± 25 %, no cap, retries
everything).

The new consumer, `kariricode/processor-pipeline` (level 1, synchronous),
wraps each processor in decorators. It needs to ask for admission without
an exception, to report success or failure with the Throwable while the
breaker classifies it, per-key rate limits with a cost and a wait hint, and
a retry decision (whether, and how long to wait) with the decorator doing
the waiting. State may live outside the process.

ARFA 1.3 §4.4.2 sketches pipeline execution with `breaker.isOpen()`,
`breaker.recordSuccess()`, `breaker.recordFailure()`, `limiter.tryAcquire()`
and `policy.shouldRetry(error)` + `retryWithBackoff`.

## Decision

Release `kariricode/contract` 2.10.0, additive over 2.9.0, with five
interfaces in `KaririCode\Contract\Resilience`:

| Interface | Members | Origin |
|---|---|---|
| `RateLimiter` | `attempt(string $key, int $cost = 1): RateLimitDecision`, `peek(string $key): RateLimitDecision`, `reset(string $key): void` | rate-limiter 4.0.0 proposal, same signatures |
| `RateLimitDecision` | `bool $allowed`, `int $limit`, `int $remaining`, `int $retryAfter`, `int $resetAfter`, all `{ get; }` | rate-limiter 4.0.0 proposal, same signatures |
| `CallPermit` | none (opaque token) | circuit-breaker 4.0.0 `Core\CallPermit` |
| `ManualCircuitBreaker extends CircuitBreaker` | `tryAcquirePermit(): ?CallPermit`, `acquirePermit(): CallPermit`, `recordSuccess(CallPermit $permit): void`, `recordFailure(CallPermit $permit, Throwable $error): void`, `releasePermit(CallPermit $permit): void` | circuit-breaker 4.0.0 manual permits, plus two methods |
| `RetryPolicy` | `shouldRetry(Throwable $error, int $attempt): bool`, `delayMilliseconds(Throwable $error, int $attempt): int` | new; no implementation yet |

The open points were closed as follows:

1. **Names follow the libraries.** The limiter keeps what rate-limiter
   4.0.0 implements (`attempt`, `$allowed`, `$retryAfter`), not the proposed
   `isAllowed()` / `retryAfterSeconds()`: Spec V4.0 prefers properties to
   getters, and the library needs no change. Parameter names match the
   implementations as well (`$error`, not `$cause`), because PHP binds named
   arguments to the implementation's parameter names.
2. **Permits, not a boolean.** A `tryAcquirePermission(): bool` with
   `recordSuccess()` / `recordFailure(Throwable)` cannot tell which admission
   an outcome belongs to. With state shared across processes, a slow call
   that finishes after the circuit reopened would count against the new
   generation, and half-open trials could not be matched to their outcomes.
   circuit-breaker 4.0.0 binds each permit to the circuit's epoch; the
   contract carries that token as `CallPermit`.
3. **Two ways to acquire.** `tryAcquirePermit()` returns null on rejection
   and never throws, as the pipeline decorator requires. `acquirePermit()`
   throws `CallNotPermittedException`, whose `retryAfterSeconds` and codes
   (7100–7102) DataFlow's asynchronous stages map to their failure policy.
   Resilience4j offers the same pair (`tryAcquirePermission`,
   `acquirePermission`).
4. **`releasePermit()` included.** A permit granted to a call that then
   does not run (denied by the rate limiter, skipped by a condition) must not
   hold a half-open trial slot until the trial window expires. With it, the
   breaker can wrap the limiter, the default aspect order of Resilience4j's
   Spring Boot integration, without recording the limiter's denial as a
   failure.
5. **No duration argument.** The permit records when the call was admitted,
   so the implementation measures the duration itself, as circuit-breaker
   4.0.0 already does for its events. A slow-call trigger can be added to a
   library without touching the contract.
6. **No state, no enum.** Consumers need admission, not the state:
   `isOpen()` answers whether the next call would be rejected. The state
   belongs to observability (the breaker's PSR-14 events) and to operators
   (the library's `snapshot()`). An enum is not an interface, and the package
   holds interfaces only (ADR-001).
7. **Retry policy.** Two questions, both given the error: whether to retry
   and how long to wait. The error in `delayMilliseconds()` lets a policy
   honour a wait the error carries (`CallNotPermittedException::$retryAfterSeconds`).
   `$attempt` counts the calls already made, all failed. The contract holds
   only the interface; the default implementation (exponential backoff with
   full jitter, Brooker 2015) belongs in a new `kariricode/retry` package that
   DataFlow and Observability can adopt instead of their own loops.
8. **The Authenticator fits the limiter.** Counting failures is admitting
   events per window: `peek($bucket)->allowed` before authenticating,
   `attempt($bucket)` on each failure, and optionally `reset($bucket)` on
   success. The quota (`$max`, `$window`) moves from each call to the
   limiter's construction; rate-limiter 4.0.0 documents a `login` limiter
   built this way (`SlidingWindowLog`, `Quota::of(5, 900)`).
9. **Timeouts stay out.** Bounding a call's duration needs an asynchronous
   runtime (ARFA level 2); a level-1 contract cannot promise it.

Correspondence with ARFA 1.3 §4.4.2:

| §4.4.2 | 2.10.0 |
|---|---|
| `breaker.isOpen()` before the call | `tryAcquirePermit()`, which admits and reserves a half-open trial; `isOpen()` stays advisory |
| `breaker.recordSuccess()` | `recordSuccess($permit)` |
| `breaker.recordFailure()` | `recordFailure($permit, $error)`; the breaker classifies |
| `limiter.tryAcquire()` | `attempt($key, $cost)->allowed` |
| `policy.shouldRetry(error)` + `retryWithBackoff` | `shouldRetry($error, $attempt)` + `delayMilliseconds($error, $attempt)`; the decorator waits |
| `stage.isCritical` | pipeline concern (`haltOnError`), not a contract |
| `stage.timeout` | out of scope (level 2) |

## Alternatives Considered

- **The proposal as written** (`tryAcquirePermission(): bool`,
  `recordSuccess()`, `recordFailure(Throwable)`). Rejected: without a
  permit, outcomes cannot be tied to the admission that produced them.
- **`isOpen()` plus recording, as §4.4.2 sketches.** Rejected: a read without
  side effect lets every caller through a half-open circuit that should admit
  only `halfOpenMaxAttempts` trials.
- **Add the manual methods to `Resilience\CircuitBreaker`.** Rejected: it
  shipped in 2.9.0 and circuit-breaker 4.0.0 implements it; new methods
  break implementers.
- **A decision object for the breaker, like `RateLimitDecision`.** Deferred:
  no consumer needs the delay on the non-throwing path, and `acquirePermit()`
  carries it. A later method can add it without breaking anyone.
- **Only `execute()` in the pipeline.** Rejected: a rate limit between
  admission and the call would be recorded as a failure, unless every
  breaker were configured to ignore the pipeline's exceptions.
- **Keep `RetryPolicy` out until an implementation exists**, as ADR-001
  prefers. Rejected: two packages already run retry loops with diverging
  formulas, the pipeline needs the port now, and the port is two methods.
- **Ship the default retry policy in processor-pipeline or DataFlow.**
  Rejected: other consumers would depend on a pipeline to get a backoff
  formula.

## Consequences

### Positive

- The consumers can drop duck typing; the pipeline composes breaker,
  limiter and retry through types only.
- rate-limiter 4.0.0 needs no source change: it deletes `contract-proposal/`
  and requires the contract.
- Outcomes stay bound to their admission across processes.

### Negative

- circuit-breaker 4.0.0 must add `tryAcquirePermit()` and `releasePermit()`,
  make `Core\CallPermit` implement the contract's `CallPermit`, and type
  `recordSuccess()` / `recordFailure()` against it (SPEC-001 §8.4).
- The two libraries name the same idea differently: `retryAfterSeconds` in
  the breaker, `retryAfter` in the limiter. Both are seconds; unifying them
  costs nothing only before either library is published.
- `RetryPolicy` has consumers but no implementation until `kariricode/retry`
  exists.
- An implementation of `ManualCircuitBreaker` provides seven methods;
  `execute()` can be written once over the other four.

## ARFA 1.3

- **P1 Immutable State Transformation:** decisions are read-only snapshots;
  permits latch once settled.
- **P2 Reactive Flow Composition:** not required at level 1; the permit
  protocol already serves asynchronous callers, and no signature is a
  promise.
- **P3 Adaptive Context Awareness:** callers adapt to rejections, denials
  and retry decisions returned as values.
- **P4 Protocol Agnosticism:** waits are plain seconds or milliseconds;
  429, 503 and `Retry-After` are mapped at the edge.
- **P5 Continuous Observability:** implementations publish their events;
  the contract adds nothing to observe.

## References

- Nygard, M. T. (2018). *Release It!* (2nd ed.). Pragmatic Bookshelf, ch. 5,
  "Stability Patterns".
- Fowler, M. (2014). *CircuitBreaker*. martinfowler.com/bliki/CircuitBreaker.html
- Resilience4j documentation: "CircuitBreaker", "RateLimiter", "Retry",
  "Getting Started with Spring Boot" (aspect order).
- Brooker, M. (2015). "Exponential Backoff And Jitter". AWS Architecture Blog.
- Kleppmann, M. (2017). *Designing Data-Intensive Applications*. O'Reilly,
  ch. 8 — fencing tokens.
- KaririCode ARFA 1.3 §4.4.2; Code Quality Directive V4.0 §27.2, §29, §30,
  E-005, E-009.
- `kariricode/circuit-breaker` 4.0.0, SPEC-001 §2 and §8;
  `kariricode/rate-limiter` 4.0.0, SPEC-001 §3 and `contract-proposal/README.md`.

## Amendment — 2026-10-01 (2.10.1)

A review of the other component records found facts this ADR did not have.
The interfaces do not change; the corrections concern implementations,
consumers and codes.

1. **A retry implementation exists.** `kariricode/async-helper` 4.0 ships
   `Contract\BackoffStrategy` (`calculateDelay(int $attempt): int`, in
   milliseconds), `Contract\RetryStrategy` (`execute(callable $operation,
   int $maxAttempts = 3): mixed`) and four strategies, among them
   `Retry\JitteredBackoff` with full, equal and decorrelated jitter; Queue
   already uses it. Decision 7 changes accordingly: the default
   `RetryPolicy` is an adapter in async-helper that classifies the error and
   takes the delay from a `BackoffStrategy`. There will be no
   `kariricode/retry`. DataFlow and Observability adopt that policy instead
   of their own loops. Async-helper's interfaces need their own Spec V4.3.3
   pass first (parameterless getters such as `getMaxDelay()`, `@package`
   tags).
2. **A fourth breaker consumer.** `kariricode/async`'s
   `Integration\CircuitBreakerBridge` receives the breaker as `object` and
   returns a promise. That is the asynchronous case `ManualCircuitBreaker`
   was designed for: acquire a permit, settle it when the promise settles.
3. **Rate-limiter codes.** rate-limiter 4.0.0 moved to the ecosystem range
   RL-8300–8399: RL-8300 invalid value (cost below 1 included), RL-8301 cost
   above capacity, RL-8310–8312 store unavailable, RL-8320 unknown limiter.
   Earlier notes that cite RL-4290–4299 are void.
4. **Documentation criteria.** Spec V4.3.3 §17 defines C3 as side effects;
   thread-safety now belongs to C2. The port docblocks state their side
   effects, and the conformance test checks C2 to C5.
