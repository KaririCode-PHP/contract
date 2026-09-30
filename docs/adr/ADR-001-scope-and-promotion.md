# ADR-001: Scope of kariricode/contract and the promotion criterion

- **Status:** Accepted
- **Date:** 2026-09-30
- **Component:** kariricode/contract 2.9.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

Spec V4.0 §13.1 routes every dependency between components through
`kariricode/contract`. What the package may hold, and when a new interface
may enter, had never been written down.

An earlier redesign (contract "V3.1", ARFA 1.2) tried to break the
ecosystem's dependency cycles by adding about 55 interfaces in 11 namespaces
at once. Most had no implementation and no consumer, several duplicated
PHP-FIG interfaces under KaririCode names, and the first version carried
promises into the contract. A contract without a consumer is a guess: its
shape is fixed before anyone uses it, and every correction afterwards breaks
its implementers.

A review of the V4 components found that most integrate without the
contract today (ClassDiscovery has zero dependencies, I18n and Runtime use
PSR packages, the data-processing components keep local contracts). The
contracts that do cross packages are the ones Configurator ADR-007 promoted
and the resilience pair designed with CircuitBreaker 4.0.0.

## Decision

1. **Scope.** The package holds interfaces, including marker interfaces,
   that more than one published package shares. It holds no classes, enums,
   traits, functions or implementations, depends on nothing but PHP, and
   contains nothing asynchronous (ARFA 1.3 level 1): promises and adapters
   live in `KaririCode\Async`.
2. **Promotion criterion.** An interface enters when a second package
   actually consumes it, and it enters with its own ADR naming both
   packages (in the owning package, as Configurator ADR-007 did, or here).
3. **PSR first.** Where a PHP-FIG interface covers the need, packages use
   it: cache between packages through PSR-16 (or PSR-6), events through
   PSR-14, logging PSR-3, container PSR-11, clock PSR-20. The contract does
   not redeclare them under other names.
4. **2.9.0 applies the criterion.** Resilience has an implementation
   (`kariricode/circuit-breaker` 4.0.0) and consumers (DataFlow,
   Observability, ProcessorPipeline, per its SPEC-001 §8). Environment and
   Configuration have implementations and consumers in Configurator and
   Dotenv (ADR-007). No interface of the V3.1 design enters.
5. **Waiting list.** The `SerializableData` marker of Spec V4.0 §9 enters
   with the first component that implements it; none does yet.

## Alternatives Considered

- **Publish the V3.1 set at once.** Rejected: interfaces without consumers
  freeze guesses, and the set overlapped PSRs and `KaririCode\Async`.
- **Keep shared contracts inside the owning component.** Rejected: every
  consumer would depend on the owner, which is the coupling Configurator
  ADR-007 removed.
- **Mirror PSR interfaces under KaririCode names** (`Cache\Cache`,
  `EventDispatcher\EventDispatcher`). Rejected: two names for one
  capability split the ecosystem and lose interoperability with libraries
  outside it.

## Consequences

### Positive

- Every new interface has at least one implementation and one consumer, so
  its shape is tested by use before it is frozen.
- The package stays small, and its release cadence follows real needs.

### Negative

- A component that needs a contract before a second consumer exists keeps
  a local interface and migrates later, as Configurator did.
- The 2.8.6 interfaces predate the criterion; some may have no consumer.
  They stay until 3.0.0 (ADR-002).

## ARFA 1.3

- **P1 Immutable State Transformation:** interfaces carry no mutable state;
  properties are declared read-only (`{ get; }`).
- **P2 Reactive Flow Composition:** contracts are synchronous; reactive and
  asynchronous composition happens in implementations and `KaririCode\Async`.
- **P4 Protocol Agnosticism:** no transport type appears in a signature.

## References

- Cwalina, K., Barton, J., & Abrams, B. (2020). *Framework Design
  Guidelines* (3rd ed.). Addison-Wesley — interface design: provide at
  least one implementation and one API that consumes each interface.
- Martin, R. C. (2002). *Agile Software Development: Principles, Patterns,
  and Practices*. Prentice Hall — Dependency Inversion, Acyclic
  Dependencies and Stable Abstractions principles.
- PHP-FIG: PSR-3, PSR-6, PSR-11, PSR-14, PSR-16, PSR-20.
- KaririCode Configurator, ADR-007 "Ecosystem Contract Promotion".
- KaririCode Code Quality Directive V4.0, §9 and §13.1.
