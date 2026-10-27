# ADR-007: Property inspection contracts

- **Status:** Accepted
- **Date:** 2026-10-27
- **Component:** kariricode/contract 2.12.0
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

## Context

The data-processing components read their rules from attributes on the
properties of a DPO (`#[Sanitize('trim')]`, `#[Validate('email')]`) and
write the processed values back. They do it through
`kariricode/property-inspector`, a sibling package, which Spec V4.3.3 §31
forbids: components couple only through this package.

Packagist, checked 2026-10-27:

| Package | Requires | Uses |
|---|---|---|
| sanitizer 1.2.3 | property-inspector `^2.0` | its handler implements `PropertyAttributeHandler` and `PropertyChangeApplier`; calls `inspect()`, `getProcessedPropertyValues()`, `applyChanges()`; builds `new AttributeAnalyzer(Sanitize::class)`; writes through `Utility\PropertyAccessor` |
| transformer 2.0.0 | property-inspector `^2.0` | the same, with `Transform::class` |
| serializer 1.1.0 | property-inspector `^2.0` | the same, with `Serialize::class` |
| validator 1.0.0 | property-inspector `^1.0` (resolves 1.2.3) | reads `getProcessedPropertyValues()` and `getProcessingResultErrors()` |

None of them reads `getProcessingResultMessages()`, and the three 2.x
handlers return `[]` from both the messages and the errors getters. The
inspector never reads a handler's getters: it only calls
`handleAttribute()`, and ignores its return value.

property-inspector's own records already plan the move. ADR-009
(Proposed) moves the four contracts to this package in its 3.0.0 "if KC
Contract accepts the promotion"; ADR-006 (Proposed) replaces in-place
writes with copy-on-write, because writing an initialized readonly
property raises `Error` on PHP 8.4 and the Spec models DPOs as readonly
classes; ADR-008 keeps `object` parameters, since its domain is any object.

Copied as they are, the four 2.x contracts would break this package's own
rules for new namespaces: three getters without parameters (§18), and an
applier that cannot update a readonly DPO (ARFA 1.3 P1).

## Decision

Add the namespace `KaririCode\Contract\Inspection` in 2.12.0 with three
interfaces, reshaped for Spec V4.3.3 and keeping the 2.x names, so that
migrating is an import plus a signature change:

```php
interface PropertyInspector
{
    /** @param class-string $attributeClass */
    public function inspect(object $object, string $attributeClass, PropertyAttributeHandler $handler): void;
}

interface PropertyAttributeHandler
{
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): void;
}

interface PropertyChangeApplier
{
    /** @template T of object @param T $object @param array<string, mixed> $changes @return T */
    public function applyChanges(object $object, array $changes): object;
}
```

1. **The attribute class is a parameter of `inspect()`.** In 2.x it is
   bound when the implementation is built (`new AttributeAnalyzer(Sanitize::class)`),
   so every processor constructs the implementation and depends on it. As a
   parameter, one instance serves every processor and a container binds it
   once. Matching follows `ReflectionAttribute::IS_INSTANCEOF`.
2. **The handler contract is `handleAttribute()` alone, returning
   `void`.** What a handler accumulates is read by the package that owns
   it, through its own API; no package reads another's handler, so the
   getters stay out (interface segregation), and the ignored return value
   becomes `void`.
3. **The applier copies.** `applyChanges()` returns a new instance and
   never writes to its argument, as property-inspector ADR-006 proposes.
   Unchanged properties are copied as stored, without set hooks, and each
   storage slot once: a property a subclass redeclares is one slot, and a
   parent's private property is a slot of its own, kept even when a
   subclass declares the same name. Changed properties are assigned, so
   their set hooks run. The constructor does not run, so
   validation comes after the copy. Measured on PHP 8.4.23: reflection may
   initialize an uninitialized readonly property from outside its class; a
   second write raises `Error`; a child's `getProperties()` omits its
   parents' private properties; a property a subclass redeclares shares its
   parent's slot; `setValue()` runs set hooks and `setRawValue()` does not. `ReflectionChangeApplier`, a unit fixture,
   implements the contract on those facts.
4. **Failures.** The inspector's own failures (an uninitialized annotated
   property, an attribute that cannot be instantiated) and the applier's
   (an unknown or unwritable name, a value of the wrong type, an object
   reflection cannot instantiate) raise exceptions implementing
   `Exception\Runtime`. An exception thrown by a handler, or by a get or
   set hook of the object, propagates unchanged, so a `Validation` raised
   while inspecting still reaches the edge as a 422; an `Error` during an
   assignment is the applier's own failure. property-inspector 2.x wraps handler
   failures (its INV-007); 3.0.0 stops wrapping.
5. **Not promoted:**
   - `AttributeAnalyzer`. With the attribute class passed to `inspect()`,
     it is consumed only inside property-inspector, where it can keep
     changing.
   - `Utility\PropertyAccessor`. It is a class; consumers write through
     `PropertyChangeApplier` instead.
   - The 2.x getters `getProcessedPropertyValues()`,
     `getProcessingResultMessages()` and `getProcessingResultErrors()`
     (point 2).
6. **The 2.x interfaces stay in property-inspector 2.x unchanged.** Its
   3.0.0 implements this namespace and drops them (ADR-009 item 5).

## Relation to ADR-001

| Interface | Implemented by | Consumed by |
|---|---|---|
| `PropertyInspector` | property-inspector 3.0.0 | sanitizer, transformer, serializer (call `inspect()` today), validator |
| `PropertyAttributeHandler` | the processors' handlers (three published) | property-inspector |
| `PropertyChangeApplier` | property-inspector 3.0.0 | sanitizer, transformer, serializer (write through `PropertyAccessor` today) |

Each interface crosses a package boundary that published releases already
cross through property-inspector's local contracts. Their shape is new in
three points (the attribute parameter, the handler without getters, the
copy). property-inspector 3.0.0 implements them on its `release/3.0.0`
branch (`db76ea7`, not yet released), and its suite runs 24 of the 25
semantic tests of SPEC-001 §2.4 under the same names. Its review found a
defect in this package's applier fixture (a redeclared property written
twice), fixed before 2.12.0 was published.

## Alternatives Considered

| Alternative | Why not |
|---|---|
| Copy the four 2.x interfaces unchanged | three getters without parameters (§18) where the conformance suite allows no deviation in a new namespace; an in-place applier cannot update a readonly DPO |
| Keep the contracts in property-inspector and let processors require it | sibling coupling (§31), which today also pulls in kariricode/exception (property-inspector ADR-009) |
| Each processor keeps its own reflection | the same analysis code in every data-processing component |
| Bind the attribute class when the implementation is built (2.x) | every processor must construct the implementation; a container binds one `PropertyInspector`, not one per attribute |
| Return the analysis (`list<InspectedProperty>`) instead of calling a handler | adds a fourth interface and rewrites every published handler, where the visitor keeps them and changes a signature |
| Wrap handler exceptions (2.x INV-007) | the wrapper's family (`Runtime`, 500) hides the handler's (`Validation`, 422) |
| Keep `getProcessingResultErrors()` | read only by validator 1.0.0, from its own handler; a validator reports through its own result |
| `applyChanges(object $object): void`, in place | `Error` on any initialized readonly property, and against P1 |
| `clone` and reinitialize | a cloned readonly property is already initialized; reinitializing it needs a `__clone()` per DPO |

## Consequences

### Positive

- Processors type against this package only and drop their `require` on
  property-inspector, and with it the transitive kariricode/exception.
- One inspector serves every processor, through one container binding.
- Sanitizers and transformers can update readonly DPOs, and the original
  stays available for auditing (P1).
- An exception raised by a handler keeps its family up to the edge.

### Negative

- Every consumer changes: the handler signature, and the applier returns a
  copy, so APIs that updated the caller's object in place must return the
  new object instead.
- The copy skips the constructor; the pipeline must validate after it
  (sanitize, then validate).
- No library implements the family yet; property-inspector 3.0.0 is the
  first, and its 2.x line keeps the old interfaces.
- `object` parameters: the domain is any object (property-inspector
  ADR-008); `@template` adds the static precision where it matters.

## ARFA 1.3

- **P1:** inspection never writes; the applier returns a new state.
- **P2:** not affected; the contracts are synchronous (level 1).
- **P3:** not affected.
- **P4:** any object, whatever transport produced it.
- **P5:** belongs to the implementations; interfaces emit nothing.

## References

- KaririCode Spec V4.3.3 §8.4, §17, §18, §31.
- kariricode/property-inspector (main, 2026-10-27): SPEC-001 (invariants
  INV-001 to INV-013), ADR-006, ADR-008, ADR-009.
- PHP RFC "Readonly properties 2.0" — https://wiki.php.net/rfc/readonly_properties_v2
- PHP RFC "Property hooks" (`ReflectionProperty::setRawValue()`) — https://wiki.php.net/rfc/property-hooks
- PHP manual, `ReflectionProperty::getAttributes()` — https://www.php.net/manual/en/reflectionproperty.getattributes.php
- PHP manual, `ReflectionClass::newInstanceWithoutConstructor()` — https://www.php.net/manual/en/reflectionclass.newinstancewithoutconstructor.php
- Gamma, E., Helm, R., Johnson, R., & Vlissides, J. (1994). *Design
  Patterns*. Addison-Wesley — Visitor.
- Martin, R. C. (2002). *Agile Software Development: Principles, Patterns,
  and Practices*. Prentice Hall — Interface Segregation Principle.
