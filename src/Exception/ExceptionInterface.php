<?php

declare(strict_types=1);

namespace KaririCode\Contract\Exception;

use Throwable;

/**
 * Root of every exception a KaririCode component throws, so that the edge,
 * a logger or a retry policy recognises the ecosystem's failures without
 * knowing each component.
 *
 * The name keeps the suffix under errata E-035 (sanctioned root contract);
 * capability interfaces never take it.
 *
 * Preconditions: implement one of the six families (Validation, NotFound,
 * Configuration, Protocol, ContainerFailure, Runtime), not this interface
 * directly; getCode() returns a code from the component's registered range
 * (Spec V4.3.3 §8.4).
 *
 * Thread-safety: an exception belongs to the call that threw it and holds
 * no shared state.
 *
 * Side effects: none.
 *
 * Example: `catch (ExceptionInterface $failure) { $logger->error($failure->getMessage(), ['code' => $failure->getCode()]); }`
 *
 * References: KaririCode Spec V4.3.3 §8.4 and errata E-035.
 *
 * @see https://www.php.net/manual/en/class.throwable.php
 */
interface ExceptionInterface extends Throwable {}
