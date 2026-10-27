<?php

declare(strict_types=1);

namespace KaririCode\Contract\Inspection;

/**
 * Receives, from a PropertyInspector, each matching attribute found on an
 * object's properties and turns the declarations into what its processor
 * needs: the rules of each property and the values to process.
 *
 * The contract stops at handleAttribute(). What a handler accumulates is
 * read by the package that owns it, through its own API; no package reads
 * another package's handler, so the 2.x getters (processed values, messages,
 * errors) are not part of it (ADR-007).
 *
 * Preconditions: $propertyName is the property's declared name; $attribute
 * is an instance of the class the caller asked the inspector for; $value is
 * the property's value at the moment of the call. Attribute instances may
 * be shared with later inspections of the same class: read them, never
 * change them.
 *
 * Thread-safety: a handler holds the state of one inspection; use one
 * instance per inspected object.
 *
 * Side effects: changes only the handler's own state and never writes to
 * the inspected object (PropertyChangeApplier writes, on a copy). An
 * exception it throws reaches the inspector's caller unchanged.
 *
 * Example:
 *
 *     final class SanitizeRules implements PropertyAttributeHandler
 *     {
 *         public private(set) array $values = [];
 *         public private(set) array $rules = [];
 *
 *         public function handleAttribute(string $propertyName, object $attribute, mixed $value): void
 *         {
 *             $this->values[$propertyName] = $value;
 *             $this->rules[$propertyName] = [...$this->rules[$propertyName] ?? [], ...$attribute->rules];
 *         }
 *     }
 *
 * References: Gamma, Helm, Johnson and Vlissides, Design Patterns (1994),
 * "Visitor"; Martin, Agile Software Development (2002), Interface
 * Segregation Principle; PHP RFC "Asymmetric Visibility v2" (the example's
 * private(set)); ADR-007 of this package.
 *
 * @see https://wiki.php.net/rfc/asymmetric-visibility-v2
 */
interface PropertyAttributeHandler
{
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): void;
}
