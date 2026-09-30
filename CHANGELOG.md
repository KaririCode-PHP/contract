# Changelog

All notable changes to `kariricode/contract` are documented here.
The format follows Keep a Changelog; versions follow Semantic Versioning.

## [2.10.0] — 2026-09-30

Additive release (ADR-004). **There is no breaking change:** every type of
2.8.6 and 2.9.0 keeps its API, which `BackwardCompatibilityTest` verifies
against Reflection snapshots of both releases.

### Added

- `Resilience\RateLimiter` and `Resilience\RateLimitDecision`, with the
  signatures `kariricode/rate-limiter` 4.0.0 implements (its
  `contract-proposal/`).
- `Resilience\CallPermit` and `Resilience\ManualCircuitBreaker` (extends
  `CircuitBreaker`): the manual permit protocol of
  `kariricode/circuit-breaker` 4.0.0, plus `tryAcquirePermit()` and
  `releasePermit()`.
- `Resilience\RetryPolicy`: whether to retry and how long to wait. No
  implementation exists yet.
- ADR-004; the 2.9.0 API snapshot; unit tests with a minimal implementation
  of each port; conformance checks for the E-005 docblock criteria and for
  synchronous signatures.

- `psalm-strict.xml` and `composer psalm-strict`: Psalm error level 1, without
  baseline, over the source added since 2.9.0; part of `composer check`.

### Changed

- `BackwardCompatibilityTest` also guards the 2.9.0 API.
- README, SPEC-001 and QD-V40-COMPLIANCE describe 2.10.0.

## [2.9.0] — 2026-09-30

Additive release on the 2.x line (ADR-002). **There is no breaking change:**
every type, method, parameter and return type of 2.8.6 is unchanged, which
`tests/Compatibility/BackwardCompatibilityTest` verifies against a
Reflection snapshot of the 2.8.6 dist.

### Added

- `Resilience\CircuitBreaker` and `Resilience\CallNotPermittedException`,
  copied literally from the patch shipped with `kariricode/circuit-breaker`
  4.0.0.
- `Environment\EnvironmentResolver`, `Configuration\ValueProcessor` and
  `Configuration\ConfigProvider`: the three contracts of Configurator
  ADR-007, with the same signatures and without the `Interface` suffix
  (ADR-003). The suffixed names were never published, so no alias exists.
- Test suites `unit` (a minimal implementation of each new interface),
  `compatibility` (2.8.6 API snapshot) and `conformance` (ARFA 1.3 /
  Spec V4.0 rules, with the named list of inherited deviations).
- ADR-001 to ADR-003, SPEC-001 and the QD-V40 compliance record (PENDING).
- PHPStan and Psalm baselines holding the 46 and 10 findings located in
  code inherited from 2.8.6; new code has none.

### Changed

- Requires PHP `^8.4` (was `^8.3`): interface properties need PHP 8.4.
  Projects on PHP 8.3 keep resolving 2.8.x; Composer does not offer them
  2.9.0.
- Development tooling follows the V4 components: PHPUnit `^11.5` (was
  `^10.5`), PHPStan `^2.1` and Psalm `^6.0` (new), PHP CS Fixer `^3.64`
  (was pinned to 3.85.1); phpinsights, PHP_CodeSniffer, Mockery and
  security-checker were dropped.
- `phpstan.neon`, `psalm.xml` and `.gitignore` replaced by the V4 versions;
  `phpunit.xml` replaced by `phpunit.xml.dist`; the `.gitattributes`
  export-ignore list follows the new tooling, so the dist still ships
  without tests.
- Docblocks of the 54 inherited files lost their `@author` (52) and
  `@license` (50) tags and the 54 blank docblock lines those left behind.
  No code line changed: with doc comments removed, the 54 files are
  byte-identical to 2.8.6.
- The 2.8.6 tests, taken from the git tag `v2.8.6` because the dist
  excludes `tests/`, run unchanged on PHPUnit 11.5 (160 tests).
- `README.md` rewritten; `composer.json` description and keywords updated;
  `extra.arfa` declares ARFA 1.3, level 1.

### Fixed

- `extra.branch-alias` mapped `dev-main` to `1.0.x-dev`; it is now
  `2.x-dev`.

### Removed

- Root files of 2.8.6 not carried into 2.9.0: `.docker/`, `.github/`,
  `.vscode/`, `Makefile`, `docker-compose.yml`, `.env.example`,
  `phpinsights.php`, `phpcs.xml`, `.php-cs-fixer.php`, `phpunit.xml`,
  `composer.lock` and `README.pt-br.md`. The CI workflow left with
  `.github/`; a new one is a prerequisite of the quality gate
  (QD-V40-COMPLIANCE §2).

### Not changed on purpose

Inherited types that break Spec V4.0 rules stay as they are, since fixing
them would break implementers; they are 3.0.0 items in QD-V40-COMPLIANCE §5.
This includes `Logging\LoggerExtendedInterface`, which PSR-4 cannot
autoload because it is declared in `LoggerExtended.php`.
