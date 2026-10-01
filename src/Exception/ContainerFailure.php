<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

/**
 * The dependency injection container could not build a service that is
 * bound: a circular dependency, an unresolvable parameter, a factory that
 * threw.
 *
 * Preconditions: an identifier that was never bound is NotFound. Containers
 * also implement PSR-11's ContainerExceptionInterface (and
 * NotFoundExceptionInterface for unknown identifiers), so PSR-11 consumers
 * keep working.
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `final class CircularDependency extends \LogicException implements ContainerFailure {}`;
 * the HTTP edge answers 500.
 *
 * References: Spec V4.3.3 §8.4; PHP-FIG PSR-11 §1.3.
 *
 * @see https://www.php-fig.org/psr/psr-11/
 */
interface ContainerFailure extends ExceptionInterface {}
