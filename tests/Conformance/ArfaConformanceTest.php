<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Conformance;

use JsonSerializable;
use KaririCode\Contract\Tests\Compatibility\ApiSnapshot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * ARFA 1.3 / Spec V4.0 conformance of kariricode/contract.
 *
 * Every rule runs over the whole src/ tree and must find exactly the
 * deviations named in INHERITED_DEVIATIONS: code frozen since 2.8.6 by the
 * 2.x compatibility promise (ADR-002), each one a 3.0.0 item of
 * docs/quality/QD-V40-COMPLIANCE.md. A new violation anywhere fails, and so
 * does a listed deviation that no longer exists. The namespaces added since
 * 2.9.0 have no exceptions, and the 2.10.0 resilience ports must document
 * the E-005 criteria.
 */
final class ArfaConformanceTest extends TestCase
{
    private const string SOURCE = __DIR__ . '/../../src';

    private const string ROOT = __DIR__ . '/../..';

    private const array NEW_NAMESPACES = [
        'KaririCode\\Contract\\Configuration\\',
        'KaririCode\\Contract\\Context\\',
        'KaririCode\\Contract\\Environment\\',
        'KaririCode\\Contract\\Exception\\',
        'KaririCode\\Contract\\Resilience\\',
        'KaririCode\\Contract\\Security\\',
    ];

    private const array NEW_TYPES = [
        'KaririCode\\Contract\\Configuration\\ConfigProvider',
        'KaririCode\\Contract\\Configuration\\ValueProcessor',
        'KaririCode\\Contract\\Context\\ContextStorage',
        'KaririCode\\Contract\\DI\\BindableContainer',
        'KaririCode\\Contract\\DI\\ServiceProvider',
        'KaririCode\\Contract\\Environment\\EnvironmentResolver',
        'KaririCode\\Contract\\Exception\\Configuration',
        'KaririCode\\Contract\\Exception\\ContainerFailure',
        'KaririCode\\Contract\\Exception\\ExceptionInterface',
        'KaririCode\\Contract\\Exception\\NotFound',
        'KaririCode\\Contract\\Exception\\Protocol',
        'KaririCode\\Contract\\Exception\\Runtime',
        'KaririCode\\Contract\\Exception\\Validation',
        'KaririCode\\Contract\\Resilience\\CallNotPermittedException',
        'KaririCode\\Contract\\Resilience\\CallPermit',
        'KaririCode\\Contract\\Resilience\\CircuitBreaker',
        'KaririCode\\Contract\\Resilience\\ManualCircuitBreaker',
        'KaririCode\\Contract\\Resilience\\RateLimitDecision',
        'KaririCode\\Contract\\Resilience\\RateLimiter',
        'KaririCode\\Contract\\Resilience\\RetryPolicy',
        'KaririCode\\Contract\\Security\\Identity',
        'KaririCode\\Contract\\SerializableData',
    ];

    /** Errata E-035: sanctioned root contracts keep the Interface suffix. */
    private const array SANCTIONED_ROOTS = [
        \KaririCode\Contract\Exception\ExceptionInterface::class,
    ];

    private const array EXCEPTION_FAMILIES = [
        \KaririCode\Contract\Exception\Configuration::class,
        \KaririCode\Contract\Exception\ContainerFailure::class,
        \KaririCode\Contract\Exception\NotFound::class,
        \KaririCode\Contract\Exception\Protocol::class,
        \KaririCode\Contract\Exception\Runtime::class,
        \KaririCode\Contract\Exception\Validation::class,
    ];

    /** Types added since 2.10.0; their docblocks follow criteria C1–C5 of Spec V4.3.3 §17. */
    private const array DOCUMENTED_TYPES = [
        \KaririCode\Contract\Context\ContextStorage::class,
        \KaririCode\Contract\DI\BindableContainer::class,
        \KaririCode\Contract\DI\ServiceProvider::class,
        \KaririCode\Contract\Exception\Configuration::class,
        \KaririCode\Contract\Exception\ContainerFailure::class,
        \KaririCode\Contract\Exception\ExceptionInterface::class,
        \KaririCode\Contract\Exception\NotFound::class,
        \KaririCode\Contract\Exception\Protocol::class,
        \KaririCode\Contract\Exception\Runtime::class,
        \KaririCode\Contract\Exception\Validation::class,
        \KaririCode\Contract\Resilience\CallNotPermittedException::class,
        \KaririCode\Contract\Security\Identity::class,
        \KaririCode\Contract\SerializableData::class,
        \KaririCode\Contract\Resilience\CallPermit::class,
        \KaririCode\Contract\Resilience\ManualCircuitBreaker::class,
        \KaririCode\Contract\Resilience\RateLimitDecision::class,
        \KaririCode\Contract\Resilience\RateLimiter::class,
        \KaririCode\Contract\Resilience\RetryPolicy::class,
    ];

    private const array INHERITED_DEVIATIONS = [
        'not-an-interface' => [
            'KaririCode\\Contract\\Processor\\Attribute\\BaseProcessorAttribute',
        ],
        'forbidden-name' => [
            'KaririCode\\Contract\\Logging\\LoggerExtendedInterface',
            'KaririCode\\Contract\\Logging\\LoggingManager',
        ],
        'zero-argument-getter' => [
            'KaririCode\\Contract\\DI\\Definition::getConcrete',
            'KaririCode\\Contract\\DI\\Definition::getParameters',
            'KaririCode\\Contract\\DI\\Definition::getTags',
            'KaririCode\\Contract\\DataStructure\\Behavioral\\IterableCollection::getIterator',
            'KaririCode\\Contract\\DataStructure\\Structural\\BPlusTreeCollection::getMaximum',
            'KaririCode\\Contract\\DataStructure\\Structural\\BPlusTreeCollection::getMinimum',
            'KaririCode\\Contract\\DataStructure\\Structural\\BPlusTreeCollection::getOrder',
            'KaririCode\\Contract\\DataStructure\\Structural\\Collection::getItems',
            'KaririCode\\Contract\\Logging\\Behavioral\\Configurable::getConfig',
            'KaririCode\\Contract\\Logging\\LogLevel::getColor',
            'KaririCode\\Contract\\Logging\\LogLevel::getLevel',
            'KaririCode\\Contract\\Logging\\LogLevel::getValue',
            'KaririCode\\Contract\\Logging\\Logger::getName',
            'KaririCode\\Contract\\Logging\\LoggerExtendedInterface::getName',
            'KaririCode\\Contract\\Logging\\LoggingManager::getName',
            'KaririCode\\Contract\\Logging\\Structural\\ContextAware::getContext',
            'KaririCode\\Contract\\Logging\\Structural\\FormatterAware::getFormatter',
            'KaririCode\\Contract\\Logging\\Structural\\HandlerAware::getHandlers',
            'KaririCode\\Contract\\Logging\\Structural\\ProcessorAware::getProcessors',
            'KaririCode\\Contract\\Processor\\Attribute\\BaseProcessorAttribute::getProcessors',
            'KaririCode\\Contract\\Processor\\Attribute\\ProcessableAttribute::getProcessors',
            'KaririCode\\Contract\\Processor\\ProcessingResult::getErrors',
            'KaririCode\\Contract\\Processor\\ProcessingResult::getProcessedData',
            'KaririCode\\Contract\\Processor\\ValidatableProcessor::getErrorKey',
        ],
        'serializes-itself' => [
            'KaririCode\\Contract\\Processor\\ProcessingResult::toArray',
        ],
        'file-not-named-after-type' => [
            'Logging/LoggerExtended.php',
        ],
        'untyped-constant' => [],
    ];

    #[Test]
    public function onlyInterfacesAreDeclared(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            if (!$type->isInterface()) {
                $found[] = $type->getName();
            }
        }

        self::assertDeviations('not-an-interface', $found);
    }

    #[Test]
    public function namesCarryNoInterfaceSuffixNorVagueNoun(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            if (in_array($type->getName(), self::SANCTIONED_ROOTS, true)) {
                continue;
            }

            if (preg_match('/Interface$|(?:Manager|Helper|Utils?)(?=[A-Z]|$)/', $type->getShortName()) === 1) {
                $found[] = $type->getName();
            }
        }

        self::assertDeviations('forbidden-name', $found);
    }

    #[Test]
    public function noGetterWithoutParameters(): void
    {
        $found = [];

        foreach (self::ownMethods() as $name => $method) {
            if (preg_match('/^get(?:[A-Z_]|$)/', $method->getName()) === 1 && $method->getNumberOfParameters() === 0) {
                $found[] = $name;
            }
        }

        self::assertDeviations('zero-argument-getter', $found);
    }

    #[Test]
    public function noTypeSerializesItself(): void
    {
        $found = [];

        foreach (self::ownMethods() as $name => $method) {
            if (in_array(strtolower($method->getName()), ['toarray', 'jsonserialize'], true)) {
                $found[] = $name;
            }
        }

        foreach (self::types() as $type) {
            if ($type->implementsInterface(JsonSerializable::class)) {
                $found[] = $type->getName();
            }
        }

        self::assertDeviations('serializes-itself', $found);
    }

    #[Test]
    public function eachFileDeclaresOneTypeNamedAfterIt(): void
    {
        $found = [];

        foreach (ApiSnapshot::capture(self::SOURCE)['files'] as $file => $types) {
            if (count($types) !== 1 || self::shortName($types[0]) !== basename($file, '.php')) {
                $found[] = $file;
            }
        }

        self::assertDeviations('file-not-named-after-type', $found);
    }

    #[Test]
    public function classConstantsAreTyped(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            foreach ($type->getReflectionConstants() as $constant) {
                if ($constant->getDeclaringClass()->getName() === $type->getName() && !$constant->isEnumCase() && !$constant->hasType()) {
                    $found[] = $type->getName() . '::' . $constant->getName();
                }
            }
        }

        self::assertDeviations('untyped-constant', $found);
    }

    #[Test]
    public function readonlyTypesDeclareNoPropertyHooksPerErrataE013(): void
    {
        $found = [];

        foreach (self::types() as $type) {
            foreach ($type->getProperties() as $property) {
                if (($type->isReadOnly() || $property->isReadOnly()) && $property->hasHooks()) {
                    $found[] = $type->getName() . '::$' . $property->getName();
                }
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function docblocksCarryNoAuthorshipTags(): void
    {
        $found = [];

        foreach (array_keys(ApiSnapshot::capture(self::SOURCE)['files']) as $file) {
            $tokens = token_get_all((string) file_get_contents(self::SOURCE . '/' . $file));

            foreach ($tokens as $token) {
                if (is_array($token) && $token[0] === T_DOC_COMMENT && preg_match('/@(?:package|author|license|copyright|version)\b/', $token[1]) === 1) {
                    $found[] = $file;

                    break;
                }
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function theRuntimeRequiresNothingButPhp(): void
    {
        $composer = json_decode((string) file_get_contents(self::ROOT . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        self::assertSame(['php' => '^8.4'], $composer['require']);
        self::assertSame(['arfa' => ['version' => '1.3', 'level' => 1]], array_intersect_key($composer['extra'], ['arfa' => true]));
    }

    #[Test]
    public function theNewNamespacesAreCheckedAndHaveNoExceptions(): void
    {
        $scanned = array_map(static fn(ReflectionClass $type): string => $type->getName(), self::types());

        foreach (self::NEW_TYPES as $type) {
            self::assertContains($type, $scanned);
        }

        $excepted = [];

        foreach (self::INHERITED_DEVIATIONS as $entries) {
            foreach ($entries as $entry) {
                foreach (self::NEW_NAMESPACES as $namespace) {
                    $directory = str_replace('\\', '/', substr($namespace, strlen('KaririCode\\Contract\\')));

                    if (str_starts_with($entry, $namespace) || str_starts_with($entry, $directory)) {
                        $excepted[] = $entry;
                    }
                }
            }
        }

        self::assertSame([], $excepted);
    }

    #[Test]
    public function theTypesAddedSince210DocumentTheFiveCriteria(): void
    {
        $missing = [];

        foreach (self::DOCUMENTED_TYPES as $port) {
            $docblock = (string) (new ReflectionClass($port))->getDocComment();
            $criteria = [
                'preconditions (C2)' => 'Preconditions',
                'thread-safety (C2)' => 'Thread-safety:',
                'side effects (C3)' => 'Side effects:',
                'example (C4)' => 'Example',
                'references (C5)' => 'References:',
            ];

            foreach ($criteria as $criterion => $marker) {
                if (!str_contains($docblock, $marker)) {
                    $missing[] = $port . ': ' . $criterion;
                }
            }

            if (!str_contains($docblock, '@see https://')) {
                $missing[] = $port . ': @see link (C5)';
            }
        }

        self::assertSame([], $missing);
    }

    #[Test]
    public function theExceptionFamiliesAreMemberFreeMarkersUnderOneRoot(): void
    {
        $root = new ReflectionClass(\KaririCode\Contract\Exception\ExceptionInterface::class);
        self::assertTrue($root->isSubclassOf(\Throwable::class));
        self::assertSame([], self::ownMembers($root));

        foreach (self::EXCEPTION_FAMILIES as $family) {
            $type = new ReflectionClass($family);

            self::assertTrue($type->isInterface(), $family);
            self::assertSame([$root->getName()], array_values(array_diff($type->getInterfaceNames(), $root->getInterfaceNames())), $family . ' extends the root directly');
            self::assertSame([], self::ownMembers($type), $family);
        }

        self::assertTrue((new ReflectionClass(\KaririCode\Contract\Resilience\CallNotPermittedException::class))->isSubclassOf(\KaririCode\Contract\Exception\Runtime::class));
    }

    #[Test]
    public function theInheritedDiContractsAreDeprecatedForRemovalIn300(): void
    {
        $deprecated = [];

        foreach (self::types() as $type) {
            if (str_starts_with($type->getName(), 'KaririCode\\Contract\\DI\\') && in_array($type->getShortName(), ['Compiler', 'Container', 'Definition', 'Provider'], true)) {
                self::assertStringContainsString('@deprecated 2.11.0 Removed in 3.0.0', (string) $type->getDocComment(), $type->getName());
                $deprecated[] = $type->getShortName();
            }
        }

        sort($deprecated);
        self::assertSame(['Compiler', 'Container', 'Definition', 'Provider'], $deprecated);
    }

    #[Test]
    public function composerDeclaresTheArfaAndAsvsLevels(): void
    {
        /** @var array{extra?: array{arfa?: array{version?: string, level?: int}, asvs?: array{version?: string, level?: int}}} $composer */
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('1.3', $composer['extra']['arfa']['version'] ?? null, 'Spec V4.3.3 §29.3 G4: arfa.version');
        self::assertSame(1, $composer['extra']['arfa']['level'] ?? null, 'G4: arfa.level');
        self::assertSame('5.0', $composer['extra']['asvs']['version'] ?? null, 'G4: asvs.version');
        self::assertContains($composer['extra']['asvs']['level'] ?? null, [1, 2, 3], 'G4: asvs.level');
    }

    #[Test]
    public function theTypesAddedSince210AreSynchronousInterfaces(): void
    {
        $found = [];

        foreach (self::DOCUMENTED_TYPES as $port) {
            $type = new ReflectionClass($port);
            self::assertTrue($type->isInterface(), $port);

            foreach ($type->getMethods() as $method) {
                $signature = (string) $method->getReturnType() . ' ' . implode(' ', array_map(
                    static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(),
                    $method->getParameters(),
                ));

                if (preg_match('/Promise|Deferred|Awaitable|Fiber|Generator/', $signature) === 1) {
                    $found[] = $port . '::' . $method->getName();
                }
            }
        }

        self::assertSame([], $found);
    }

    #[Test]
    public function staticAnalysisBaselinesCoverInheritedFilesOnly(): void
    {
        foreach (['phpstan-baseline.neon', 'psalm-baseline.xml'] as $baseline) {
            $contents = (string) file_get_contents(self::ROOT . '/' . $baseline);
            self::assertNotSame('', $contents, $baseline);

            foreach (['src/Resilience/', 'src/Environment/', 'src/Configuration/'] as $newDirectory) {
                self::assertStringNotContainsString($newDirectory, $contents, $baseline);
            }
        }
    }

    /**
     * @param list<string> $found
     */
    private static function assertDeviations(string $rule, array $found): void
    {
        $expected = self::INHERITED_DEVIATIONS[$rule];
        sort($expected);
        sort($found);

        self::assertSame($expected, $found, sprintf('Rule "%s": deviations must match the inherited list exactly.', $rule));
    }

    /**
     * @return list<ReflectionClass<object>>
     */
    private static function types(): array
    {
        return array_map(
            static fn(string $name): ReflectionClass => new ReflectionClass($name),
            array_keys(ApiSnapshot::capture(self::SOURCE)['types']),
        );
    }

    /**
     * @return array<string, ReflectionMethod>
     */
    private static function ownMethods(): array
    {
        $methods = [];

        foreach (self::types() as $type) {
            foreach ($type->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() === $type->getName()) {
                    $methods[$type->getName() . '::' . $method->getName()] = $method;
                }
            }
        }

        return $methods;
    }

    private static function shortName(string $type): string
    {
        $separator = strrpos($type, '\\');

        return $separator === false ? $type : substr($type, $separator + 1);
    }

    /**
     * @param ReflectionClass<object> $type
     *
     * @return list<string>
     */
    private static function ownMembers(ReflectionClass $type): array
    {
        $members = [];

        foreach ($type->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() === $type->getName()) {
                $members[] = $method->getName() . '()';
            }
        }

        foreach ($type->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $type->getName()) {
                $members[] = '$' . $property->getName();
            }
        }

        return $members;
    }
}
