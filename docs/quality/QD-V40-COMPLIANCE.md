# QD-V40 Compliance — kariricode/contract 2.10.0

- **Status: PENDING**
- **Date:** 2026-09-30
- **Component:** kariricode/contract 2.10.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

The package is not compliant until the gates below pass in the project's
CI. The local evidence in §3 supports that expectation; it is not the
verdict (E-006).

## 1. Gates required for PASS

| Gate | Command | CI result |
|---|---|---|
| Syntax | `php -l` on every PHP file | pending |
| Inherited 2.8.6 tests | `composer test-inherited` | pending |
| Unit tests of the contracts added since 2.9.0 | `composer test-unit` | pending |
| Backward compatibility with 2.8.6 and 2.9.0 | `composer test-compatibility` | pending |
| ARFA 1.3 / Spec V4.0 conformance | `composer test-conformance` | pending |
| PHPStan, level max (baseline: inherited files only) | `composer phpstan` | pending |
| Psalm, error level 2 (baseline: inherited files only) | `composer psalm` | pending |
| Psalm, error level 1 (strict, no baseline): source added since 2.9.0 | `composer psalm-strict` | pending |
| Coding standard, PER-CS 2.0 set (new code) | `composer cs-check` | pending |
| Clean install from the release tarball | `composer install` in an empty directory, then the gates above | pending |

## 2. Blocking prerequisites

1. A CI workflow on PHP 8.4 running the gates above. The 2.8.6 workflow
   (`.github/workflows/kariri-ci-cd.yml`) left with the rest of the old
   tooling.
2. Tags: 2.9.0 then 2.10.0, or 2.10.0 alone; circuit-breaker's `^2.9`
   accepts 2.10.0.
3. rate-limiter 4.0.0 must drop `contract-proposal/` before it is installed
   together with this package: two autoload entries for
   `KaririCode\Contract\Resilience\` would load whichever comes first.

## 3. Local evidence (not CI)

Measured 2026-09-30 in an isolated sandbox.

| Item | Version |
|---|---|
| PHP | 8.4.23 (CLI, static build) |
| PHPUnit | 11.5.56 |
| PHPStan | 2.2.16 |
| Psalm | 6.19.1 |
| PHP CS Fixer | 3.95.27 |

| Gate | Result |
|---|---|
| `php -l` | no syntax errors in any PHP file |
| PHPUnit, four suites | 217 tests, 3,795 assertions, all passing: inherited 160/393, unit 39/3,337, compatibility 5/28, conformance 13/37 |
| PHPStan level max | 0 errors; the baseline still holds the same 46 findings in 17 inherited files |
| Psalm error level 2 | 0 errors; the baseline still holds the same 10 findings in 6 inherited files |
| Psalm error level 1, `psalm-strict.xml` | 0 errors in `src/Configuration`, `src/Environment`, `src/Resilience`, without baseline. Over test code, level 1 reports 23 findings, all in test helpers first written for 2.9.0 and none in the files added in 2.10.0 (open item 10) |
| PHP CS Fixer, dry run | 0 of 33 files in scope to fix; inherited code is out of scope |
| Rate-limiter signatures | `RateLimiter` and `RateLimitDecision` have the same Reflection API as rate-limiter 4.0.0's `contract-proposal/` |
| Coverage | not measured: no Xdebug or PCOV in the build |

No finding needed a fix during the 2.10.0 run.

## 4. Scope of the evidence

- Inherited source and tests are frozen: they are analysed, but their
  findings sit in the baselines and the fixer skips them.
- The compatibility test covers what Reflection sees; see SPEC-001 §12.
- `RetryPolicy` semantics rest on a test fixture: no library implements
  the port yet.

## 5. Items for 3.0.0 (inherited from 2.8.6)

Each item needs a breaking change and a migration of the dependents
(ADR-002).

1. `Processor\Attribute\BaseProcessorAttribute`: an abstract class in a
   package of interfaces; move it to the package that uses it.
2. `Logging\LoggerExtendedInterface`: suffixed name, and not autoloadable
   because its file is `LoggerExtended.php`.
3. `Logging\LoggingManager`: vague noun in the name.
4. 24 getters without parameters (SPEC-001 §11): become interface
   properties `{ get; }` or named queries.
5. `Processor\ProcessingResult::toArray()` (E-001).
6. `BaseProcessorAttribute::getMessage()` parameter renamed against its
   interface (named-argument break).
7. `ProcessorRegistry::getContextProcessors()` documents a missing
   `HashMap` type.
8. Unused `Psr\Log\LoggerInterface` import in `Logging\Behavioral\Loggable`.
9. `DataStructure\Structural\Serializable` (`serialize()` /
   `deserialize()`), against E-001.
10. Empty both baselines (46 PHPStan, 10 Psalm findings) and bring the four
    inherited files into the coding standard.
11. Review which inherited interfaces have a consumer (ADR-001) before
    carrying them into 3.0.0.

## 6. Open items for 2.x

1. **`SerializableData`** (Spec V4.0 §9): no V4 component implements it in
   the project records; it enters with the first one, under ADR-001.
2. **E-013 numbering:** Spec V4.3.3 uses E-013 for "DPO jargon" and
   registers the readonly-hooks rule as E-040; rename when V4.3.3 is adopted
   (SPEC-001 §9).
3. **2.9.0 consumers:** Configurator and Dotenv to `^2.9` and unsuffixed
   imports (SPEC-001 §8.2).
4. **circuit-breaker 4.0.0:** add `tryAcquirePermit()` and `releasePermit()`,
   implement the contract's `CallPermit`, require `^2.10` (SPEC-001 §8.4).
5. **rate-limiter 4.0.0:** delete `contract-proposal/`, require `^2.10`.
6. **`RetryPolicy` implementation:** a `kariricode/retry` package with
   exponential backoff and full jitter; none exists yet.
7. **Naming:** `retryAfterSeconds` (breaker) and `retryAfter` (limiter)
   are both seconds. Unify them before either library is published, or keep
   both until 3.0.0.
8. **Error range:** rate-limiter 4.0.0 uses RL-4290–4299, while the
   ecosystem registry of KC Console reserves 8300–8399 for the rate limiter.
   Not a contract matter; recorded so it is not lost.
9. **Traceability:** the libraries' file and line references could not be
   checked against their tarballs; ADR-004 cites their design records.
10. **Psalm level 1 over tests:** 23 findings, mostly `Mixed*` from
    Reflection and JSON handling in the 2.9.0 test helpers (`ApiSnapshot`,
    `BackwardCompatibilityTest`, `ArfaConformanceTest`, three unit tests).
    They pass level 2; type them before extending `psalm-strict.xml` to
    `tests/`.
