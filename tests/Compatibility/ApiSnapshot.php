<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Compatibility;

use Closure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use RuntimeException;
use SplFileInfo;

/**
 * Captures the public API of a source tree through Reflection.
 *
 * One record per declared type: kind, modifiers, parent, every interface it
 * satisfies, and its own constants, enum cases, properties and methods
 * (public and protected only; private members are not API). Docblocks are
 * ignored, so only what PHP enforces is compared.
 *
 * Files are located by path and their declared types by the tokenizer, so a
 * type whose file name breaks PSR-4 (2.8.6 ships one) is still captured.
 *
 * Example: `$api = ApiSnapshot::capture(__DIR__ . '/../../src');`
 */
final class ApiSnapshot
{
    private const string NAMESPACE_PREFIX = 'KaririCode\\Contract\\';

    /**
     * @return array{types: array<string, array<string, mixed>>, files: array<string, list<string>>}
     */
    public static function capture(string $sourceDirectory): array
    {
        $root = realpath($sourceDirectory);

        if ($root === false || !is_dir($root)) {
            throw new RuntimeException(sprintf('Source directory not found: %s', $sourceDirectory));
        }

        $files = self::declaredTypesByFile($root);
        $autoloader = self::fallbackAutoloader($root);
        spl_autoload_register($autoloader);

        $types = [];

        try {
            foreach ($files as $relativePath => $typeNames) {
                foreach ($typeNames as $typeName) {
                    if (!self::isLoaded($typeName)) {
                        require_once $root . '/' . $relativePath;
                    }

                    $types[$typeName] = self::describeType(new ReflectionClass($typeName));
                }
            }
        } finally {
            spl_autoload_unregister($autoloader);
        }

        ksort($types);
        ksort($files);

        return ['types' => $types, 'files' => $files];
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function toJson(array $snapshot): string
    {
        return json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ) . "\n";
    }

    /**
     * @return array<string, list<string>> relative path => fully qualified type names
     */
    private static function declaredTypesByFile(string $root): array
    {
        $result = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($root) + 1);
            $source = file_get_contents($file->getPathname());

            if ($source === false) {
                throw new RuntimeException(sprintf('Cannot read %s', $file->getPathname()));
            }

            $result[$relativePath] = self::declaredTypes($source);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private static function declaredTypes(string $source): array
    {
        $tokens = token_get_all($source);
        $namespace = '';
        $types = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = self::nextName($tokens, $i);
                continue;
            }

            if (!in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            if ($token[0] === T_CLASS && self::isClassConstantOrAnonymous($tokens, $i)) {
                continue;
            }

            $name = self::nextName($tokens, $i);
            $types[] = ltrim($namespace . '\\' . $name, '\\');
        }

        return $types;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function nextName(array $tokens, int $from): string
    {
        $count = count($tokens);

        for ($j = $from + 1; $j < $count; ++$j) {
            $token = $tokens[$j];

            if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED], true)) {
                return $token[1];
            }
        }

        throw new RuntimeException('Declaration without a name.');
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private static function isClassConstantOrAnonymous(array $tokens, int $index): bool
    {
        for ($j = $index - 1; $j >= 0; --$j) {
            $token = $tokens[$j];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return (is_array($token) && in_array($token[0], [T_DOUBLE_COLON, T_NEW], true));
        }

        return false;
    }

    /**
     * PSR-4 loader for the tree being captured, registered only during
     * capture() so that a standalone run (dump-api.php) can resolve parents.
     */
    private static function fallbackAutoloader(string $root): Closure
    {
        return static function (string $class) use ($root): void {
            if (!str_starts_with($class, self::NAMESPACE_PREFIX)) {
                return;
            }

            $path = $root . '/' . str_replace('\\', '/', substr($class, strlen(self::NAMESPACE_PREFIX))) . '.php';

            if (is_file($path)) {
                require_once $path;
            }
        };
    }

    private static function isLoaded(string $typeName): bool
    {
        return class_exists($typeName, false)
            || interface_exists($typeName, false)
            || trait_exists($typeName, false)
            || enum_exists($typeName, false);
    }

    /**
     * @param ReflectionClass<object> $type
     *
     * @return array<string, mixed>
     */
    private static function describeType(ReflectionClass $type): array
    {
        $interfaces = $type->getInterfaceNames();
        sort($interfaces);

        $record = [
            'kind' => self::kindOf($type),
            'abstract' => $type->isAbstract() && !$type->isInterface(),
            'final' => $type->isFinal(),
            'readonly' => $type->isReadOnly(),
            'parent' => $type->getParentClass() === false ? null : $type->getParentClass()->getName(),
            'interfaces' => $interfaces,
            'constants' => self::describeConstants($type),
            'properties' => self::describeProperties($type),
            'methods' => self::describeMethods($type),
        ];

        if ($type->isEnum()) {
            $record['enum'] = self::describeEnum(new ReflectionEnum($type->getName()));
        }

        return $record;
    }

    /**
     * @param ReflectionClass<object> $type
     */
    private static function kindOf(ReflectionClass $type): string
    {
        return match (true) {
            $type->isInterface() => 'interface',
            $type->isTrait() => 'trait',
            $type->isEnum() => 'enum',
            default => 'class',
        };
    }

    /**
     * @param ReflectionClass<object> $type
     *
     * @return array<string, array<string, mixed>>
     */
    private static function describeConstants(ReflectionClass $type): array
    {
        $result = [];

        foreach ($type->getReflectionConstants() as $constant) {
            if ($constant->getDeclaringClass()->getName() !== $type->getName() || $constant->isPrivate() || $constant->isEnumCase()) {
                continue;
            }

            $result[$constant->getName()] = [
                'visibility' => self::constantVisibility($constant),
                'final' => $constant->isFinal(),
                'type' => self::typeName($constant->getType()),
                'value' => self::export($constant->getValue()),
            ];
        }

        ksort($result);

        return $result;
    }

    /**
     * @param ReflectionClass<object> $type
     *
     * @return array<string, array<string, mixed>>
     */
    private static function describeProperties(ReflectionClass $type): array
    {
        $result = [];

        foreach ($type->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() !== $type->getName() || $property->isPrivate()) {
                continue;
            }

            $hooks = array_keys($property->getHooks());
            sort($hooks);

            $result[$property->getName()] = [
                'visibility' => $property->isPublic() ? 'public' : 'protected',
                'static' => $property->isStatic(),
                'readonly' => $property->isReadOnly(),
                'abstract' => $property->isAbstract(),
                'final' => $property->isFinal(),
                'virtual' => $property->isVirtual(),
                'setVisibility' => self::setVisibility($property),
                'type' => self::typeName($property->getType()),
                'hooks' => $hooks,
                'default' => $property->hasDefaultValue() ? self::export($property->getDefaultValue()) : null,
            ];
        }

        ksort($result);

        return $result;
    }

    /**
     * @param ReflectionClass<object> $type
     *
     * @return array<string, array<string, mixed>>
     */
    private static function describeMethods(ReflectionClass $type): array
    {
        $result = [];

        foreach ($type->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $type->getName() || $method->isPrivate()) {
                continue;
            }

            $result[$method->getName()] = [
                'visibility' => $method->isPublic() ? 'public' : 'protected',
                'static' => $method->isStatic(),
                'abstract' => $method->isAbstract(),
                'final' => $method->isFinal(),
                'byReference' => $method->returnsReference(),
                'return' => self::typeName($method->getReturnType()),
                'parameters' => array_map(self::describeParameter(...), $method->getParameters()),
            ];
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function describeParameter(ReflectionParameter $parameter): array
    {
        $default = null;

        if ($parameter->isDefaultValueAvailable()) {
            $default = $parameter->isDefaultValueConstant()
                ? 'const:' . $parameter->getDefaultValueConstantName()
                : self::export($parameter->getDefaultValue());
        }

        return [
            'name' => $parameter->getName(),
            'type' => self::typeName($parameter->getType()),
            'optional' => $parameter->isOptional(),
            'variadic' => $parameter->isVariadic(),
            'byReference' => $parameter->isPassedByReference(),
            'promoted' => $parameter->isPromoted(),
            'default' => $default,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function describeEnum(ReflectionEnum $enum): array
    {
        $cases = [];

        foreach ($enum->getCases() as $case) {
            $cases[$case->getName()] = $enum->isBacked() ? self::export($case->getBackingValue()) : null;
        }

        return ['backing' => self::typeName($enum->getBackingType()), 'cases' => $cases];
    }

    private static function constantVisibility(ReflectionClassConstant $constant): string
    {
        return $constant->isPublic() ? 'public' : 'protected';
    }

    private static function setVisibility(ReflectionProperty $property): ?string
    {
        return match (true) {
            $property->isPrivateSet() => 'private',
            $property->isProtectedSet() => 'protected',
            default => null,
        };
    }

    private static function typeName(?ReflectionType $type): ?string
    {
        return $type === null ? null : (string) $type;
    }

    private static function export(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }
}
