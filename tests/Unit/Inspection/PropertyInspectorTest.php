<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection;

use KaririCode\Contract\Exception\Runtime;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidEmail;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Broken;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Rule;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Validate;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\BrokenDeclaration;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Defaults;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Draft;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\HalfBuilt;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\ReflectionPropertyInspector;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\RejectingHandler;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\RuleCollector;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\SignUp;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Unreadable;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PropertyInspectorTest extends TestCase
{
    #[Test]
    public function everyMatchingAttributeReachesTheHandlerInDeclarationOrder(): void
    {
        $collector = new RuleCollector();

        (new ReflectionPropertyInspector())->inspect(self::signUp(), Sanitize::class, $collector);

        self::assertSame(['trim', 'lowercase'], $collector->rules['email'], 'a Validate is not a Sanitize; order is declaration order');
        self::assertSame(['trim'], $collector->rules['name'], 'private properties of the class itself are inspected');
        self::assertArrayNotHasKey('age', $collector->rules, 'a property without the attribute never reaches the handler');
    }

    #[Test]
    public function anInterfaceMatchesEveryAttributeThatImplementsIt(): void
    {
        $collector = new RuleCollector();

        (new ReflectionPropertyInspector())->inspect(self::signUp(), Rule::class, $collector);

        self::assertSame(['trim', 'email', 'lowercase'], $collector->rules['email']);
    }

    #[Test]
    public function inheritedProtectedPropertiesAreInspectedButAParentsPrivateOnesAreNot(): void
    {
        $collector = new RuleCollector();

        (new ReflectionPropertyInspector())->inspect(self::signUp(), Sanitize::class, $collector);

        self::assertSame(['trim'], $collector->rules['tenant']);
        self::assertArrayNotHasKey('audit', $collector->rules, 'Account::$audit belongs to Account');
    }

    #[Test]
    public function theHandlerReceivesTheValueHeldDuringTheCall(): void
    {
        $draft = new Draft();
        $inspector = new ReflectionPropertyInspector();

        $inspector->inspect($draft, Sanitize::class, $first = new RuleCollector());
        $draft->title = 'second';
        $inspector->inspect($draft, Sanitize::class, $second = new RuleCollector());

        self::assertSame([['title', 'trim', 'first']], $first->calls);
        self::assertSame([['title', 'trim', 'second']], $second->calls, 'values are never cached');
    }

    #[Test]
    public function inspectingNeverWritesToTheObject(): void
    {
        $draft = new Draft();
        $before = clone $draft;

        (new ReflectionPropertyInspector())->inspect($draft, Sanitize::class, new RuleCollector());

        self::assertEquals($before, $draft);
    }

    #[Test]
    public function anUninitializedAnnotatedPropertyIsARuntimeFault(): void
    {
        $this->expectException(Runtime::class);

        (new ReflectionPropertyInspector())->inspect(new HalfBuilt(), Sanitize::class, new RuleCollector());
    }

    #[Test]
    public function anAttributeThatCannotBeInstantiatedIsARuntimeFaultRaisedBeforeTheHandlerRuns(): void
    {
        $collector = new RuleCollector();

        try {
            (new ReflectionPropertyInspector())->inspect(new BrokenDeclaration(), Broken::class, $collector);
            self::fail('an unusable declaration must not pass');
        } catch (Runtime $fault) {
            self::assertInstanceOf(LogicException::class, $fault->getPrevious());
        }

        self::assertSame([], $collector->calls);
    }

    #[Test]
    public function anExceptionFromTheHandlerKeepsItsFamily(): void
    {
        try {
            (new ReflectionPropertyInspector())->inspect(self::signUp(), Validate::class, new RejectingHandler());
            self::fail('the handler rejected the value');
        } catch (\Throwable $thrown) {
            self::assertInstanceOf(InvalidEmail::class, $thrown, 'the handler\'s own exception, not a wrapper');
            self::assertInstanceOf(Validation::class, $thrown, 'the edge still sees a 422, not the inspector\'s Runtime');
        }
    }

    #[Test]
    public function staticPropertiesAreNotInspected(): void
    {
        $collector = new RuleCollector();

        (new ReflectionPropertyInspector())->inspect(new Defaults(), Sanitize::class, $collector);

        self::assertSame([['title', 'trim', ' own ']], $collector->calls);
    }

    #[Test]
    public function anExceptionFromAGetHookPropagatesUnchanged(): void
    {
        try {
            (new ReflectionPropertyInspector())->inspect(new Unreadable(), Sanitize::class, new RuleCollector());
            self::fail('the get hook threw');
        } catch (\Throwable $thrown) {
            self::assertInstanceOf(InvalidEmail::class, $thrown, 'the object\'s own exception, not a wrapper');
            self::assertInstanceOf(Validation::class, $thrown);
        }
    }

    private static function signUp(): SignUp
    {
        return new SignUp(email: ' A@B.org ', name: ' Walmir ', age: 30);
    }
}
