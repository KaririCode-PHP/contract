# QD-V40 Compliance — kariricode/contract 2.11.0

- **Status: PENDING**
- **Date:** 2026-10-01
- **Component:** kariricode/contract 2.11.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

The package is not compliant until the gates below pass in the project's
CI. The workflow exists (`.github/workflows/ci.yml`, ADR-006) but has not
run on GitHub yet. The local evidence in §3 supports the expectation; it is
not the verdict (E-006).

## 1. Gates required for PASS

| Gate | Command | CI result |
|---|---|---|
| Composer metadata and lock file | `composer validate --strict` | pending |
| Syntax | `php -l` on every PHP file | pending |
| Inherited 2.8.6 tests | `composer test-inherited` | pending |
| Unit tests of the contracts added since 2.9.0 | `composer test-unit` | pending |
| Backward compatibility with 2.8.6, 2.9.0 and 2.10.0 | `composer test-compatibility` | pending |
| ARFA 1.3 / Spec V4.3.3 conformance, levels declared (G4) | `composer test-conformance` | pending |
| PHPStan, level max (baseline: inherited files only) | `composer phpstan` | pending |
| Psalm, error level 2 (baseline: inherited files only) | `composer psalm` | pending |
| Psalm, error level 1 (strict, no baseline): source added since 2.9.0 | `composer psalm-strict` | pending |
| Coding standard, PER-CS 2.0 set (new code and `tools/`) | `composer cs-check` | pending |
| Known vulnerabilities in the locked toolchain | `composer security` | pending |
| SBOM | `composer sbom` | pending |
| Clean install from the release tarball | `composer install` in an empty directory, then the gates above | pending |

## 2. Blocking prerequisites

1. A first green run of `.github/workflows/ci.yml` on GitHub.
2. Signed tags (`git tag -s`) for 2.9.0, 2.10.0, 2.10.1 and 2.11.0, in that
   order, or only for the latest; `^2.9`, `^2.10` and `^2.11` all accept
   2.11.0.
3. rate-limiter 4.0.0 must drop `contract-proposal/` before it is installed
   together with this package: two autoload entries for
   `KaririCode\Contract\Resilience\` would load whichever comes first.

## 3. Local evidence (not CI)

Measured 2026-10-01 in an isolated sandbox, with the toolchain of the
committed lock file.

| Item | Version |
|---|---|
| PHP | 8.4.23 (CLI, static build) |
| PHPUnit | 11.5.56 |
| PHPStan | 2.2.16 |
| Psalm | 6.19.1 |
| PHP CS Fixer | 3.95.27 |

| Gate | Result |
|---|---|
| `composer validate --strict` | valid; the lock file matches `composer.json` |
| `php -l` | no syntax errors in any PHP file |
| PHPUnit, four suites | 260 tests, 3,938 assertions, all passing: inherited 160/393, unit 76/3,408, compatibility 8/45, conformance 16/92 |
| PHPStan level max | 0 errors; the baseline still holds the same 46 findings in 17 inherited files |
| Psalm error level 2 | 0 errors; the baseline still holds the same 10 findings in 6 inherited files; `DeprecatedInterface` is suppressed only in `DI/Compiler.php` and `DI/Provider.php`, where the deprecated types reference each other |
| Psalm error level 1, `psalm-strict.xml` | 0 errors, without baseline, in the source added since 2.9.0. Over test code, level 1 reports 23 findings, all in test helpers first written for 2.9.0 (open item 9) |
| PHP CS Fixer, dry run | 0 of 64 files in scope to fix; inherited code is out of scope |
| `composer security` | no security vulnerability advisories found |
| `composer sbom` | CycloneDX 1.5: 0 runtime and 95 development components |
| Rate-limiter signatures | `RateLimiter` and `RateLimitDecision` have the same Reflection API as rate-limiter 4.0.0's `contract-proposal/` |
| Coverage | not measured: no Xdebug or PCOV in the build |
| Mutation score, complexity | not applicable: the package holds no executable code (ADR-006) |

## 4. Scope of the evidence

- Inherited source and tests are frozen: they are analysed, but their
  findings sit in the baselines and the fixer skips them.
- The compatibility test covers what Reflection sees; see SPEC-001 §12.
- The semantics of `RetryPolicy` and of the 2.11.0 families rest on unit
  fixtures: no library implements them yet.
- `asvs.level` is 1 because the package has no runtime code of its own; the
  declaration exists for gate G4.

## 5. Items for 3.0.0

Each item needs a breaking change and a migration of the dependents
(ADR-002).

1. Remove the deprecated `DI\Provider`, `DI\Container`, `DI\Definition`
   and `DI\Compiler` (ADR-005).
2. `Processor\Attribute\BaseProcessorAttribute`: an abstract class in a
   package of interfaces; move it to the package that uses it.
3. `Logging\LoggerExtendedInterface`: suffixed name, and not autoloadable
   because its file is `LoggerExtended.php`.
4. `Logging\LoggingManager`: vague noun in the name.
5. 24 getters without parameters (SPEC-001 §11): become interface
   properties `{ get; }` or named queries.
6. `Processor\ProcessingResult::toArray()` (E-001).
7. `BaseProcessorAttribute::getMessage()` parameter renamed against its
   interface (named-argument break).
8. `ProcessorRegistry::getContextProcessors()` documents a missing
   `HashMap` type.
9. Unused `Psr\Log\LoggerInterface` import in `Logging\Behavioral\Loggable`.
10. `DataStructure\Structural\Serializable` (`serialize()` /
    `deserialize()`), against E-001; `SerializableData` replaces it.
11. Empty both baselines (46 PHPStan, 10 Psalm findings) and bring the four
    inherited files into the coding standard.
12. Review which inherited interfaces have a consumer (ADR-001):
    processor-pipeline 4.0 implements only `Processor` and
    `ConfigurableProcessor` and asks for an immutable pipeline contract.

## 6. Open items for 2.x

1. **Migrations of 2.11.0** (SPEC-001 §8.5): authenticator, authorization,
   runtime, observability, tenancy, dependency-injection, the framework and
   every component's exceptions and providers.
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
   are both seconds; kept as the libraries implement them.
8. **Spec numbering:** some documents still cite the V4.0 numbering
   (§27.2, §29, §30); Spec V4.3.3 numbers documentation §17, antipatterns
   §18, ecosystem integration §31 and release governance §32.
9. **Psalm level 1 over tests:** 23 findings, mostly `Mixed*` from
   Reflection and JSON handling in the 2.9.0 test helpers (`ApiSnapshot`,
   `BackwardCompatibilityTest`, `ArfaConformanceTest`, three unit tests).
   They pass level 2; type them before extending `psalm-strict.xml` to
   `tests/`.
10. **2.9.0 docblocks:** four of the five types added in 2.9.0
    (`CircuitBreaker`, `EnvironmentResolver`, `ValueProcessor`,
    `ConfigProvider`) were copied from the implementing libraries and do not
    carry the C1–C5 markers yet.
11. **Traceability:** the libraries' file and line references could not be
    checked against their tarballs; ADR-004 cites their design records.
