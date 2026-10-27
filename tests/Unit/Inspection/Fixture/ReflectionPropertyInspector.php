<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Inspection\PropertyAttributeHandler;
use KaririCode\Contract\Inspection\PropertyInspector;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use Throwable;

/**
 * Minimal PropertyInspector over Reflection. Every annotated property is read
 * before the handler sees any of them, so an inspection failure never leaves
 * a handler half-filled; the handler runs outside every catch, so its own
 * exceptions reach the caller unchanged.
 *
 * Example: `$inspector->inspect($signUp, Sanitize::class, $collector);`
 */
final class ReflectionPropertyInspector implements PropertyInspector
{
    #[Override]
    public function inspect(object $object, string $attributeClass, PropertyAttributeHandler $handler): void
    {
        foreach ($this->matches($object, $attributeClass) as $match) {
            foreach ($match[1] as $attribute) {
                $handler->handleAttribute($match[0], $attribute, $match[2]);
            }
        }
    }

    /**
     * @param class-string $attributeClass
     *
     * @return list<array{string, list<object>, mixed}>
     */
    private function matches(object $object, string $attributeClass): array
    {
        $matches = [];

        foreach ((new ReflectionClass($object))->getProperties() as $property) {
            $declared = $property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

            if ($property->isStatic() || $declared === []) {
                continue;
            }

            if (!$property->isInitialized($object)) {
                throw new InspectionFailed(sprintf('%s::$%s carries a rule but is not initialized.', $object::class, $property->getName()));
            }

            try {
                $attributes = array_map(static fn(ReflectionAttribute $attribute): object => $attribute->newInstance(), $declared);
            } catch (Throwable $cause) {
                throw new InspectionFailed(sprintf('An attribute of %s::$%s cannot be instantiated.', $object::class, $property->getName()), previous: $cause);
            }

            $matches[] = [$property->getName(), $attributes, $property->getValue($object)];
        }

        return $matches;
    }
}
