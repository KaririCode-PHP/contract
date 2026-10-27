<?php

declare(strict_types=1);

namespace KaririCode\Contract\Inspection;

/**
 * Reads the declarative rules of an object: hands every property that
 * carries an attribute of the requested class to a PropertyAttributeHandler,
 * with the attribute instance and the property's current value. It is how a
 * processor (sanitizer, validator, transformer, serializer) finds the
 * `#[Sanitize]` or `#[Validate]` declarations of a DPO without reflection
 * code of its own.
 *
 * Preconditions: $attributeClass names an attribute class, or an interface
 * that attribute classes implement; an attribute matches when it is an
 * instance of it, subclasses and implementers included. The properties
 * inspected are the non-static properties of the object's class, whatever
 * their visibility, plus the public and protected ones it inherits; a
 * private property of a parent class belongs to that class and is not
 * inspected. handleAttribute() runs once per matching attribute instance,
 * the attributes of one property in declaration order, with the value the
 * property holds during the call (values are never cached). A property that
 * carries a matching attribute must be initialized.
 *
 * Thread-safety: implementations may cache reflection metadata per class and
 * share attribute instances between calls; they keep no state about the
 * objects they inspect, so one instance serves every caller.
 *
 * Side effects: calls the handler and never writes to $object. A failure of
 * the inspection itself (an uninitialized annotated property, an attribute
 * that cannot be instantiated) raises an exception implementing
 * Exception\Runtime. An exception thrown by the handler, or by a get hook
 * of the inspected object while its value is read, propagates unchanged, so
 * its family still reaches the edge (a Validation stays a 422).
 *
 * Example:
 *
 *     $rules = new SanitizeRules();                    // a PropertyAttributeHandler
 *     $inspector->inspect($signUp, Sanitize::class, $rules);
 *     $result = $engine->sanitize($rules->values, $rules->rules);
 *
 * References: PHP manual, ReflectionProperty::getAttributes() with
 * ReflectionAttribute::IS_INSTANCEOF; kariricode/property-inspector
 * SPEC-001 (invariants INV-001 to INV-013), ADR-008 (object parameters) and
 * ADR-010 (handler and hook exceptions propagate); ADR-007 of this package.
 *
 * @see https://www.php.net/manual/en/reflectionproperty.getattributes.php
 */
interface PropertyInspector
{
    /**
     * @param class-string $attributeClass
     */
    public function inspect(object $object, string $attributeClass, PropertyAttributeHandler $handler): void;
}
