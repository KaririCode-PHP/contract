# ADR-006: Supply chain and release governance

- **Status:** Accepted
- **Date:** 2026-10-01
- **Component:** kariricode/contract 2.11.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

Spec V4.3.3 §32 asks every component for a committed `composer.lock`, an
SBOM per release, signed tags and artifacts, pinned CI actions and a
dependency review on every change (OWASP Top 10:2025 A03). Up to 2.10.1 this
package ignored its lock file and had no CI. kariricode/rate-limiter and
kariricode/console already follow the rule with a `tools/sbom.php` and a
pinned workflow (rate-limiter ADR-006).

Two facts make the rule cheap here. The package has no runtime dependency,
so the lock file pins only the development toolchain; and a library's lock
file has no effect on the projects that install the library.

## Decision

| Item | Implementation |
|---|---|
| Lock file | `composer.lock` committed and kept out of the dist archive (`export-ignore`); CI installs exactly the toolchain that passed |
| Known vulnerabilities | `composer security` (`composer audit --locked`), run in CI |
| SBOM | `composer sbom` runs `tools/sbom.php`: CycloneDX 1.5 JSON built from `composer.lock`, runtime packages as `required`, development tools as `excluded`, version taken from the tag; uploaded as a CI artifact |
| CI | `.github/workflows/ci.yml`: `composer validate --strict`, `composer install`, `composer check`, `composer security`, `composer sbom`; `permissions: contents: read`; checkout without persisted credentials |
| Pinned actions | full commit SHA with the release in a comment: `actions/checkout` v7.0.1, `shivammathur/setup-php` 2.37.2 and `actions/upload-artifact` v7.0.1 (the pins rate-limiter uses), `actions/dependency-review-action` v5.0.0 |
| Dependency review | on every pull request |
| Signed tags | a release step (`git tag -s vX.Y.Z`); the repository cannot enforce it by itself |

Not applicable: mutation testing (§19.4) and complexity budgets measure
executable code, and the package holds interfaces only. QD-V40-COMPLIANCE
records both as not applicable.

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Leave the lock file out, as Composer allows for libraries | CI would test whatever versions resolve that day, so a gate result could not be reproduced; the Spec asks for the lock file |
| CycloneDX's Composer plugin | a plugin runs code at install time and needs `allow-plugins`; the ecosystem already uses `tools/sbom.php` |
| Actions pinned by tag (`@v7`) | a tag can be moved: in March 2025 the tags of `tj-actions/changed-files` were repointed to a malicious commit (CVE-2025-30066) |

## Consequences

### Positive

- A gate result can be reproduced from the commit that produced it.
- Every release has an SBOM and an audit result.

### Negative

- SHAs and the lock file need deliberate updates (an update bot or a
  scheduled review).
- The SBOM describes the development toolchain only; the projects that
  install the package have their own.

## ARFA 1.3

Not affected: the decision concerns delivery, not the contracts.

## References

- KaririCode Spec V4.3.3 §19.6 and §32; OWASP Top 10:2025, A03.
- Composer documentation, "Libraries", section on the lock file.
- OWASP CycloneDX Specification 1.5.
- GitHub Docs, "Security hardening for GitHub Actions" (pin actions to a full-length commit SHA).
- CVE-2025-30066 (tj-actions/changed-files).
- kariricode/rate-limiter ADR-006.
