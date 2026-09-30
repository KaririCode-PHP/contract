# ADR-003: Contract names without suffix

- **Status:** Accepted
- **Date:** 2026-09-30
- **Component:** kariricode/contract 2.9.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

Spec V4.0 lists the `Interface` suffix as an antipattern: an interface is
the contract, so it is named after its role (`interface Cache {}`). The 2.x
line already follows this (`Processor\Processor`, `DataStructure\Map`),
with one inherited exception, `Logging\LoggerExtendedInterface`.

Configurator ADR-007 created three suffixed contracts for the never
published `^4.1`: `EnvironmentResolverInterface`,
`ValueProcessorInterface` and `ConfigProviderInterface`, and deprecated its
own unsuffixed local aliases. Pending this decision, the naming check of
Spec V4.3.3 only reports suffixed `KaririCode\Contract\*` interfaces instead
of failing.

The resilience pair was designed together with CircuitBreaker 4.0.0 and
shipped as a patch next to it.

## Decision

1. **One convention.** Every contract is named after its role, without the
   `Interface` suffix.
2. **ADR-007 contracts renamed before first publication.** They are
   published as `Environment\EnvironmentResolver`,
   `Configuration\ValueProcessor` and `Configuration\ConfigProvider`, with
   the same method signatures (compared by Reflection). The suffixed names
   were never published, so nothing depends on them and no alias is kept.
3. **Accessors.** None of the three declares a `get*` method without
   parameters, so no method became an interface property.
   `namespace()`, `defaults()` and `schema()` take no argument, but they
   are named queries without the `get` prefix; they keep the ADR-007
   signatures that every Configurator provider implements.
4. **Resilience from CircuitBreaker.** `Resilience\CircuitBreaker` and
   `Resilience\CallNotPermittedException` are literal copies of the
   `kariricode-contract-resilience/` patch of CircuitBreaker 4.0.0, whose
   ADR-003 records the design. `retryAfterSeconds` is an interface property
   (`{ get; }`) that a plain `readonly` property satisfies (SPEC-001 §9).

## Alternatives Considered

- **Publish the suffixed names, as ADR-007 wrote.** Rejected: they would be
  the only suffixed names of the line after `LoggerExtendedInterface`, and
  3.0.0 would have to rename them.
- **Publish both, with deprecated suffixed aliases.** Rejected: an alias
  protects existing users, and there are none.
- **Follow PHP-FIG's naming bylaw**, which requires the suffix for PSR
  interfaces. Rejected for KaririCode's own contracts (Spec V4.0); PSR
  interfaces keep their names wherever they are used.
- **Turn `namespace()`, `defaults()` and `schema()` into properties.**
  Rejected: the getter rule does not require it, and it would break every
  existing provider.

## Consequences

### Positive

- One naming rule for the whole line; the Spec's naming check can fail
  instead of only reporting.
- Configurator and Dotenv only change imports; method bodies stay.

### Negative

- Configurator 4.2's ADR-007, SPEC and deprecated local aliases mention the
  suffixed names and need editing (SPEC-001 §8).
- `LoggerExtendedInterface` stays the only suffixed name until 3.0.0.

## ARFA 1.3

- **P1 Immutable State Transformation:** the only property of the new
  contracts is read-only by declaration.
- **P4 Protocol Agnosticism:** names describe roles, not transports or
  implementations.

## References

- KaririCode Code Quality Directive V4.0 — prohibited antipatterns
  (interface suffix, getters without hooks).
- KaririCode Configurator, ADR-007.
- `kariricode/circuit-breaker` 4.0.0, ADR-003 and SPEC-001 §2, §9.
- PHP-FIG, "PSR Naming Conventions" bylaw.
- PHP RFC "Property Hooks" (PHP 8.4).
