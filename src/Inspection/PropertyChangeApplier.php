<?php

declare(strict_types=1);

namespace KaririCode\Contract\Inspection;

/**
 * Writes processed values back by copy: returns a new instance of the
 * object's class in which the named properties hold their new values and
 * every other property keeps the original's, leaving the original
 * untouched. It is how a sanitizer or transformer updates a readonly DPO,
 * which cannot be written in place (ARFA 1.3 P1).
 *
 * Preconditions: $object is an instance of a user-defined class whose state
 * lives in declared properties (an internal class such as DateTimeImmutable
 * keeps state that reflection cannot copy); each key of $changes names a
 * property a PropertyInspector would inspect, and each value fits that
 * property's type. Each storage slot is copied once, as stored, without
 * running set hooks: a property a subclass redeclares is one slot, and a
 * private property of a parent class is a slot of its own, kept even when a
 * subclass declares the same name. Changed properties are written as an
 * assignment would, so their set hooks run. An uninitialized property stays
 * uninitialized unless $changes sets it. The constructor does not run, so
 * validate the copy, not the original (sanitize, then validate).
 *
 * Thread-safety: implementations keep no state about the objects they copy;
 * one instance serves every caller.
 *
 * Side effects: allocates the copy and never writes to $object; the copy is
 * a new instance even when $changes is empty. A key that names no writable
 * property (unknown, static, or a parent's private property), a value its
 * property's type rejects, or an object that reflection cannot instantiate
 * without its constructor (an enum) raises an exception implementing
 * Exception\Runtime instead of returning a partial copy. An Exception thrown
 * by a set hook propagates unchanged; an Error during the assignment is the
 * applier's own failure.
 *
 * Example:
 *
 *     $clean = $applier->applyChanges($signUp, ['email' => 'walmir@kariricode.org']);
 *     // $clean !== $signUp, and $signUp->email is unchanged
 *
 * References: PHP RFC "Readonly properties 2.0" (reflection may initialize
 * a readonly property); PHP RFC "Property hooks"
 * (ReflectionProperty::setRawValue()); kariricode/property-inspector
 * ADR-006 (copy-on-write) and ADR-010 (hook exceptions propagate); ADR-007
 * of this package.
 *
 * @see https://wiki.php.net/rfc/readonly_properties_v2
 */
interface PropertyChangeApplier
{
    /**
     * @template T of object
     *
     * @param T                    $object
     * @param array<string, mixed> $changes new values keyed by property name
     *
     * @return T
     */
    public function applyChanges(object $object, array $changes): object;
}
