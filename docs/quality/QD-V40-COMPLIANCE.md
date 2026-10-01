# QD-V40 Compliance — kariricode/contract 2.10.1

- **Status: PENDING**
- **Date:** 2026-10-01
- **Component:** kariricode/contract 2.10.1
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
| Backward compatibility with 2.8.6, 2.9.0 and 2.10.0 | `composer test-compatibility` | pending |
| ARFA 1.3 / Spec V4.3.3 conformance, levels declared (G4) | `composer test-conformance` | pending |
| PHPStan, level max (baseline: inherited files only) | `composer phpstan` | pending |
| Psalm, error level 2 (baseline: inherited files only) | `composer psalm` | pending |
| Psalm, error level 1 (strict, no baseline): source added since 2.9.0 | `composer psalm-strict` | pending |
| Coding standard, PER-CS 2.0 set (new code) | `composer cs-check` | pending |
| Clean install from the release tarball | `composer install` in an empty directory, then the gates above | pending |

## 2. Blocking prerequisites

1. A CI workflow on PHP 8.4 running the gates above, with pinned actions
   (Spec V4.3.3 §32).
2. Tags: 2.9.0, 2.10.0 and 2.10.1 in order, or only the latest; `^2.9` and
   `^2.10` both accept 2.10.1.
3. rate-limiter 4.0.0 must drop `contract-proposal/` before it is installed
   together with this package: two autoload entries for
   `KaririCode\Contract\Resilience\` would load whichever comes first.

## 3. Local evidence (not CI)

Measured 2026-10-01 in an isolated sandbox.

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
| PHPUnit, four suites | 220 tests, 3,810 assertions, all passing: inherited 160/393, unit 39/3,337, compatibility 7/39, conformance 14/41 |
| PHPStan level max | 0 errors; the baseline still holds the same 46 findings in 17 inherited files |
| Psalm error level 2 | 0 errors; the baseline still holds the same 10 findings in 6 inherited files |
| Psalm error level 1, `psalm-strict.xml` | 0 errors in `src/Configuration`, `src/Environment`, `src/Resilience`, without baseline. Over test code, level 1 reports 23 findings, all in test helpers first written for 2.9.0 (open item 10) |
| PHP CS Fixer, dry run | 0 of 33 files in scope to fix; inherited code is out of scope |
| Rate-limiter signatures | `RateLimiter` and `RateLimitDecision` have the same Reflection API as rate-limiter 4.0.0's `contract-proposal/` |
| Coverage, mutation score | not measured: no Xdebug or PCOV in the build; Infection has nothing to mutate in an interface-only package |

## 4. Scope of the evidence

- Inherited source and tests are frozen: they are analysed, but their
  findings sit in the baselines and the fixer skips them.
- The compatibility test covers what Reflection sees; see SPEC-001 §12.
- `RetryPolicy` semantics rest on a test fixture: no library implements
  the port yet.
- `asvs.level` is 1 because the package has no runtime code of its own; the
  declaration exists for gate G4.

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
    carrying them into 3.0.0: processor-pipeline 4.0 implements only
    `Processor` and `ConfigurableProcessor` and asks for an immutable
    pipeline contract; no V4 container implements `DI\Container`.

## 6. Open items for 2.x

1. **ADR-005 (proposed):** exception families, service providers, identity,
   `SerializableData` and `ContextStorage` for 2.11.0; three open points
   await confirmation.
2. **E-013 numbering:** Spec V4.3.3 uses E-013 for "DPO jargon" and
   registers the readonly-hooks rule as E-040; rename when V4.3.3 is adopted
   (SPEC-001 §9).
3. **2.9.0 consumers:** Configurator and Dotenv to `^2.9` and unsuffixed
   imports (SPEC-001 §8.2).
4. **circuit-breaker 4.0.0:** add `tryAcquirePermit()` and `releasePermit()`,
   implement the contract's `CallPermit`, require `^2.10` (SPEC-001 §8.4).
5. **rate-limiter 4.0.0:** delete `contract-proposal/`, require `^2.10`.
6. **`RetryPolicy` implementation:** an adapter in `kariricode/async-helper`
   over its `BackoffStrategy` (ADR-004 amendment).
7. **Naming:** `retryAfterSeconds` (breaker) and `retryAfter` (limiter)
   are both seconds; kept as the libraries implement them (confirmed).
8. **Spec numbering:** some documents still cite the V4.0 numbering
   (§27.2, §29, §30); Spec V4.3.3 numbers documentation §17, antipatterns
   §18, ecosystem integration §31 and release governance §32.
9. **Traceability:** the libraries' file and line references could not be
   checked against their tarballs; ADR-004 cites their design records.
10. **Psalm level 1 over tests:** 23 findings, mostly `Mixed*` from
    Reflection and JSON handling in the 2.9.0 test helpers (`ApiSnapshot`,
    `BackwardCompatibilityTest`, `ArfaConformanceTest`, three unit tests).
    They pass level 2; type them before extending `psalm-strict.xml` to
    `tests/`.
11. **2.9.0 docblocks:** the five types added in 2.9.0 were copied from the
    implementing libraries and do not carry the C1–C5 markers; add them
    with 2.11.0.
12. **Supply chain (§32):** `composer.lock` is git-ignored, and there is no
    SBOM and no CI yet; the Spec asks for a committed lock file, an SBOM per
    release and pinned CI actions. Decision pending.
