# ADR-008: Quality toolchain through KaririCode Devkit

- **Status:** Accepted; amends ADR-006 (lock file and CI rows)
- **Date:** 2026-11-27
- **Component:** kariricode/contract 2.12.x
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

ADR-006 put the development toolchain (PHPUnit, PHPStan, Psalm,
PHP-CS-Fixer) in `require-dev` and pinned it through the committed
`composer.lock`. The ecosystem now installs the toolchain with KaririCode
Devkit (`kcode`): components carry no QA package in `require-dev`, and kcode
installs the tools into `.kcode/vendor/`.

Devkit 1.2.1 can run a component's own configs instead of its generated
defaults (`'root_configs' => true`, Devkit ADR-009), so the gates of
QD-V40-COMPLIANCE §1 do not change: PHPStan level max, Psalm level 2 and
level 1 (strict), PER-CS, and the four test suites.

## Decision

| Item | Before (ADR-006) | Now |
|---|---|---|
| Toolchain | `require-dev`, pinned by `composer.lock` | `devkit.php` → `tools`, pinned to the versions 2.12.0 passed with: PHPUnit 11.5.56, PHPStan 2.2.16, Psalm 6.19.1, PHP-CS-Fixer 3.95.27 |
| Configs | root files | the same root files, used through `'root_configs' => true` |
| kcode itself | — | CI downloads kcode 1.2.1 and checks its SHA-256 before running it |
| Lock file | pins the toolchain | committed and empty: the package has no runtime dependency |
| Known vulnerabilities | `composer audit --locked` | `composer audit --locked --working-dir=.kcode`, over the toolchain kcode installed |
| SBOM | development components from `composer.lock` | development components from `.kcode/composer.lock` |
| Commands | `composer check` and the scripts behind it | unchanged; the scripts call kcode |

Actions stay pinned by commit SHA, and the dependency review stays on every
pull request.

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Keep the toolchain in `require-dev` | every other component installs it through kcode; two toolchain models in one ecosystem |
| kcode with its generated configs | they run PHPStan level 9 and Psalm level 3 and have no conformance suite: weaker than §29.1 |
| Unpinned tools (`^11.5`) | a gate result could not be reproduced from the commit that produced it (§32) |

## Consequences

### Positive

- One toolchain model across the ecosystem, with the gates of this package
  unchanged.
- Direct tool versions are pinned in a file reviewers read.

### Negative

- Transitive versions of the toolchain are resolved at install time until a
  `devkit.lock` is committed (`kcode init --lock`).
- Contributors install kcode once (README, Development).
