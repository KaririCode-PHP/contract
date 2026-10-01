<?php

declare(strict_types=1);

namespace KaririCode\Contract\Resilience;

/**
 * Proof that a circuit breaker admitted one call. The caller hands it back
 * exactly once: to recordSuccess(), recordFailure() or releasePermit().
 *
 * Opaque on purpose. The permit ties the outcome to the circuit generation
 * that admitted the call, so a late outcome (a slow call that finishes
 * after the circuit reopened) cannot steer the next generation. Only the
 * breaker that issued it reads it.
 *
 * Preconditions: settle a permit once, on the breaker that issued it;
 * implementations reject a second settlement and a foreign permit.
 *
 * Thread-safety: a permit belongs to one call; never share it between
 * concurrent calls.
 *
 * Side effects: none of its own; settling it through the breaker writes
 * circuit state.
 *
 * Example: `$permit = $breaker->tryAcquirePermit() ?? throw new StageSkipped($stage);`
 *
 * References: Resilience4j, "CircuitBreaker" (permissions); Kleppmann,
 * Designing Data-Intensive Applications, ch. 8 (fencing tokens).
 *
 * @see https://resilience4j.readme.io/docs/circuitbreaker
 */
interface CallPermit {}
