# kariricode/contract — Documentation

Index of the design records for version 2.10.1. Read them in this order: the
specification states what the package contains and promises, the ADRs state
why, and the quality record states what has been verified.

| Document | Kind | Subject |
|---|---|---|
| [SPEC-001](spec/SPEC-001-contract.md) | Specification | inventory, new signatures, compatibility promise, consumer migration (§8), errata E-013 (§9), ARFA / Spec V4.0 matrix |
| [ADR-001](adr/ADR-001-scope-and-promotion.md) | Decision | what belongs in the contract; promotion needs a second real consumer and an ADR; PSR-16 / PSR-14 between packages |
| [ADR-002](adr/ADR-002-release-2.9.0-on-the-2.x-line.md) | Decision | 2.9.0 instead of 4.x; PHP ^8.4 |
| [ADR-003](adr/ADR-003-unsuffixed-names.md) | Decision | names without suffix; ADR-007 contracts renamed before first publication; Resilience from CircuitBreaker |
| [ADR-004](adr/ADR-004-resilience-ports.md) | Decision | resilience ports: rate limiter, manual circuit breaker with permits, retry policy; amended in 2.10.1 |
| [ADR-005](adr/ADR-005-shared-cross-cutting-contracts.md) | Proposal | shared contracts for 2.11.0: exception families, service providers, identity, serialization marker, request context |
| [QD-V40-COMPLIANCE](quality/QD-V40-COMPLIANCE.md) | Quality record | gates, local evidence, 3.0.0 items — **PENDING** |

## Architecture at a glance

```
             implements                            types against
 packages ───────────────▶  kariricode/contract  ◀──────────────── packages
                            interfaces only
                            depends on PHP ≥ 8.4, nothing else

 Resilience      ◀── circuit-breaker 4.0.0, rate-limiter 4.0.0 (implement; async-helper planned for RetryPolicy)
                 ◀── processor-pipeline, dataflow, observability, authenticator, async (consume)
 Environment     ◀── dotenv, configurator (implement and consume)
 Configuration   ◀── components (providers), configurator (processors, builder)
 2.8.6 contracts ◀── unchanged; processor-pipeline and five more on Packagist
```

## Conventions

- Documents are written in English, like the code.
- Every ADR has the header block (Status, Date, Component, Authors) and the
  sections Context, Decision, Alternatives Considered, Consequences,
  ARFA 1.3 and References.
- Performance or quality statements appear only with a measurement and its
  environment (E-006). The compliance record stays PENDING until the gates
  run in CI.
