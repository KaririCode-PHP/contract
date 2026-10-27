<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use Error;
use KaririCode\Contract\Inspection\PropertyChangeApplier;
use Override;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Minimal copy-on-write PropertyChangeApplier (property-inspector ADR-006):
 * a new instance without the constructor, every storage slot copied raw
 * once, then each change written as an assignment. Names are resolved before
 * anything is allocated.
 *
 * Example: `$clean = $applier->applyChanges($signUp, ['email' => 'a@b.org']);`
 */
final class ReflectionChangeApplier implements PropertyChangeApplier
{
    #[Override]
    public function applyChanges(object $object, array $changes): object
    {
        $class = new ReflectionClass($object);
        $targets = $this->targets($class, $changes);
        $copy = $this->blank($class);

        foreach ($this->storageSlots($class) as $property) {
            if (!$this->isTarget($property, $targets) && $property->isInitialized($object)) {
                $property->setRawValue($copy, $property->getRawValue($object));
            }
        }

        foreach ($targets as $name => $property) {
            try {
                $property->setValue($copy, $changes[$name]);
            } catch (Error $cause) {
                throw new ChangeRejected(sprintf('%s::$%s rejects the new value.', $class->getName(), $name), previous: $cause);
            }
        }

        return $copy;
    }

    /**
     * @param ReflectionClass<object> $class
     * @param array<string, mixed>    $changes
     *
     * @return array<string, ReflectionProperty>
     */
    private function targets(ReflectionClass $class, array $changes): array
    {
        $targets = [];

        foreach (array_keys($changes) as $name) {
            try {
                $property = $class->getProperty($name);
            } catch (ReflectionException $cause) {
                throw new ChangeRejected(sprintf('%s has no property named "%s".', $class->getName(), $name), previous: $cause);
            }

            if ($property->isStatic()) {
                throw new ChangeRejected(sprintf('%s::$%s is static.', $class->getName(), $name));
            }

            $targets[$name] = $property;
        }

        return $targets;
    }

    /**
     * @template T of object
     *
     * @param ReflectionClass<T> $class
     *
     * @return T
     */
    private function blank(ReflectionClass $class): object
    {
        try {
            return $class->newInstanceWithoutConstructor();
        } catch (ReflectionException | Error $cause) {
            throw new ChangeRejected(sprintf('%s cannot be copied without its constructor.', $class->getName()), previous: $cause);
        }
    }

    /**
     * Every storage slot of the class hierarchy, once. The walk is needed
     * because a child's getProperties() omits its parents' private
     * properties. A private property is a slot of its declaring class; a
     * public or protected name is one slot however many classes declare it,
     * so only its most derived declaration is kept.
     *
     * @param ReflectionClass<object> $class
     *
     * @return list<ReflectionProperty>
     */
    private function storageSlots(ReflectionClass $class): array
    {
        $slots = [];
        $shared = [];

        for ($declaring = $class; $declaring !== false; $declaring = $declaring->getParentClass()) {
            foreach ($declaring->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() !== $declaring->getName() || $property->isStatic() || $property->isVirtual()) {
                    continue;
                }

                if ($property->isPrivate()) {
                    $slots[] = $property;
                } elseif (!isset($shared[$property->getName()])) {
                    $shared[$property->getName()] = true;
                    $slots[] = $property;
                }
            }
        }

        return $slots;
    }

    /**
     * getProperty() returns the most derived declaration of a name, the one
     * storageSlots() keeps for a shared name; a parent's private property
     * with the same name is another slot.
     *
     * @param array<string, ReflectionProperty> $targets
     */
    private function isTarget(ReflectionProperty $property, array $targets): bool
    {
        $target = $targets[$property->getName()] ?? null;

        return $target !== null && $target->getDeclaringClass()->getName() === $property->getDeclaringClass()->getName();
    }
}
