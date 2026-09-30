# ADR-002: Release 2.9.0 on the 2.x line instead of 4.x

- **Status:** Accepted
- **Date:** 2026-09-30
- **Component:** kariricode/contract 2.9.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>
- **Supersedes:** the `kariricode/contract ^4.1` requirement of Configurator ADR-007

## Context

The last published version is 2.8.6 (2025-08-11). Configurator 4.2
(ADR-007) and its Dotenv bridge were written against
`kariricode/contract ^4.1`, a version that does not exist.

On Packagist, checked on 2026-09-30, the latest tagged version of each
`kariricode/*` package that requires the contract asks for:

| Package | Version | Requires contract | Requires PHP |
|---|---|---|---|
| kariricode/data-structure | v1.1.3 | ^2.0 | ^8.3 |
| kariricode/configurator | v1.0.0 | ^2.6 | ^8.3 |
| kariricode/logging | v1.2.0 | ^2.6 | ^8.3 |
| kariricode/validator | 1.0.0 | ^2.7 | ^8.3 |
| kariricode/processor-pipeline | v2.0.0 | ^2.8 | ^8.4 |
| kariricode/property-inspector | v2.0.0 | ^2.8 | ^8.4 |

`^2.8` means `>=2.8.0 <3.0.0` and `^4.1` means `>=4.1.0 <5.0.0`. The two
ranges do not intersect, so Composer cannot install ProcessorPipeline and
Configurator 4.2 in the same application, although Configurator's
`ConfigurationProcessor` exists to run inside a pipeline.

Adding interfaces is backwards compatible (Semantic Versioning §7), so it
fits a minor release. `kariricode/circuit-breaker` 4.0.0 already requires
`^2.9` and cannot be installed from Packagist until 2.9.0 is tagged.

The resilience contract declares an interface property
(`public ?int $retryAfterSeconds { get; }`), a PHP 8.4 feature; 2.8.6
required PHP `^8.3`.

## Decision

1. **Publish 2.9.0**, an additive minor release on the 2.x line. Every
   2.8.6 type keeps its API, checked by `BackwardCompatibilityTest` against
   a Reflection snapshot of the 2.8.6 dist.
2. **Require PHP `^8.4`.** Composer skips a release whose `php` requirement
   the platform does not meet, so `^2.x` projects on PHP 8.3 keep
   receiving 2.8.6, and the dependents above stay installable.
3. **Consumers.** Configurator and Dotenv move from `^4.1` to `^2.9`;
   CircuitBreaker keeps `^2.9`; ProcessorPipeline (`^2.8`) changes nothing
   (SPEC-001 §8).
4. **3.0.0 later.** Inherited deviations from Spec V4.0 stay, listed by
   name, until a 3.0.0 published together with the migration of its
   dependents.

## Alternatives Considered

- **4.1.x, as ADR-007 assumed.** Rejected: it satisfies Configurator but
  none of the six published dependents, so every package would have to
  migrate at once, and 3.x would be skipped without content.
- **3.0.0 now, fixing the inherited deviations.** Rejected for now: it
  breaks implementers of 2.x interfaces while no dependent has migrated.
- **Stay on PHP `^8.3` and expose the delay as `getRetryAfterSeconds()`.**
  Rejected: a getter without parameters is a Spec V4.0 antipattern, and
  CircuitBreaker 4.0.0 is already implemented against the property.
- **A separate `kariricode/contract-resilience` package.** Rejected: it
  splits the contract and adds a second dependency graph for one pair of
  interfaces.

## Consequences

### Positive

- One application can install ProcessorPipeline, Configurator, Dotenv and
  CircuitBreaker together.
- No published dependent has to change its constraint.

### Negative

- Projects on PHP 8.3 do not get the new contracts.
- Inherited deviations live on until 3.0.0.
- Configurator and Dotenv must change their constraint and imports; the
  change is mechanical (SPEC-001 §8).

## ARFA 1.3

- **P1 Immutable State Transformation:** a published contract is treated as
  immutable; changes arrive as new types, never as edits to existing ones.
- **P4 Protocol Agnosticism:** the platform requirement is the only new
  constraint; no transport or runtime dependency is added.

## References

- Semantic Versioning 2.0.0, §7 (minor) and §8 (major).
- Composer documentation: "Versions and constraints" (caret operator) and
  platform packages (`php`).
- PHP RFC "Property Hooks" (PHP 8.4) — properties in interfaces.
- KaririCode Configurator, ADR-007.
- `kariricode/circuit-breaker` 4.0.0, SPEC-001 §2 and §8.
