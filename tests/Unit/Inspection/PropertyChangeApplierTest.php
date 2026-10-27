<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection;

use KaririCode\Contract\Exception\Runtime;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Contract\Tests\Unit\Exception\Fixture\InvalidEmail;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Attribute\Sanitize;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Invoice;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Ledger;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Newsletter;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\ReflectionChangeApplier;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\ReflectionPropertyInspector;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\RuleCollector;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\SignUp;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Status;
use KaririCode\Contract\Tests\Unit\Inspection\Fixture\Subscription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use TypeError;

final class PropertyChangeApplierTest extends TestCase
{
    #[Test]
    public function theCopyHoldsTheChangesAndTheReadonlyOriginalIsUntouched(): void
    {
        $signUp = self::signUp();

        $copy = (new ReflectionChangeApplier())->applyChanges($signUp, ['email' => 'a@b.org', 'name' => 'Walmir']);

        self::assertNotSame($signUp, $copy);
        self::assertSame(['a@b.org', 'Walmir', 30], [$copy->email, $copy->name(), $copy->age]);
        self::assertSame([' A@B.org ', ' Walmir '], [$signUp->email, $signUp->name()]);
    }

    #[Test]
    public function unchangedStateIsCopiedIncludingAParentsPrivateProperties(): void
    {
        $copy = (new ReflectionChangeApplier())->applyChanges(self::signUp(), ['age' => 31]);

        self::assertSame(31, $copy->age);
        self::assertSame(' acme ', $copy->tenant());
        self::assertSame('created', $copy->audit(), 'Account::$audit is invisible to SignUp::getProperties()');
    }

    #[Test]
    public function anInheritedProtectedPropertyCanBeChanged(): void
    {
        $signUp = self::signUp();

        $copy = (new ReflectionChangeApplier())->applyChanges($signUp, ['tenant' => 'acme']);

        self::assertSame(['acme', ' acme '], [$copy->tenant(), $signUp->tenant()]);
    }

    #[Test]
    public function aParentsPrivatePropertyCannotBeNamed(): void
    {
        $this->expectException(Runtime::class);

        (new ReflectionChangeApplier())->applyChanges(self::signUp(), ['audit' => 'forged']);
    }

    #[Test]
    public function aChangeRunsTheSetHookAndACopiedValueDoesNot(): void
    {
        $original = new Newsletter('A@B.org');
        $applier = new ReflectionChangeApplier();

        $copied = $applier->applyChanges($original, []);
        $changed = $applier->applyChanges($original, ['email' => 'NEW@B.org']);

        self::assertSame([1, 'a@b.org'], [$copied->writes, $copied->email], 'copied as stored');
        self::assertSame([2, 'new@b.org'], [$changed->writes, $changed->email], 'written as an assignment');
        self::assertSame([1, 'a@b.org'], [$original->writes, $original->email]);
    }

    #[Test]
    public function anUninitializedPropertyStaysUninitializedUnlessChanged(): void
    {
        $original = new Newsletter('a@b.org');
        $applier = new ReflectionChangeApplier();
        $topic = new ReflectionProperty(Newsletter::class, 'topic');

        self::assertFalse($topic->isInitialized($applier->applyChanges($original, [])));
        self::assertSame('php', $applier->applyChanges($original, ['topic' => 'php'])->topic);
    }

    #[Test]
    public function anEmptyChangeSetStillYieldsANewEqualInstance(): void
    {
        $signUp = self::signUp();

        $copy = (new ReflectionChangeApplier())->applyChanges($signUp, []);

        self::assertNotSame($signUp, $copy);
        self::assertEquals($signUp, $copy);
    }

    #[Test]
    public function anUnknownPropertyIsARuntimeFault(): void
    {
        $this->expectException(Runtime::class);

        (new ReflectionChangeApplier())->applyChanges(self::signUp(), ['nickname' => 'w']);
    }

    #[Test]
    public function aValueOfTheWrongTypeIsARuntimeFault(): void
    {
        try {
            (new ReflectionChangeApplier())->applyChanges(self::signUp(), ['age' => 'thirty']);
            self::fail('an int property cannot hold a string');
        } catch (Runtime $fault) {
            self::assertInstanceOf(TypeError::class, $fault->getPrevious());
        }
    }

    #[Test]
    public function aPropertyRedeclaredByASubclassIsWrittenOnce(): void
    {
        $invoice = new Invoice();
        $applier = new ReflectionChangeApplier();

        self::assertSame('INV-1', $applier->applyChanges($invoice, [])->code(), 'copied once, not once per declaring class');
        self::assertSame('INV-2', $applier->applyChanges($invoice, ['code' => 'INV-2'])->code());
        self::assertSame('INV-1', $invoice->code());
    }

    #[Test]
    public function aParentsPrivatePropertyNamedLikeAChildPropertyIsItsOwnSlot(): void
    {
        $copy = (new ReflectionChangeApplier())->applyChanges(new Invoice(), ['note' => 'changed']);

        self::assertSame(['changed', 'ledger-private'], [$copy->note, $copy->ledgerNote()]);
    }

    #[Test]
    public function aStaticPropertyCannotBeChanged(): void
    {
        try {
            (new ReflectionChangeApplier())->applyChanges(new Invoice(), ['opened' => 1]);
            self::fail('a static property is not part of the object');
        } catch (Runtime) {
            self::assertSame(0, Ledger::$opened);
        }
    }

    #[Test]
    public function anEnumCannotBeCopied(): void
    {
        $this->expectException(Runtime::class);

        (new ReflectionChangeApplier())->applyChanges(Status::Draft, []);
    }

    #[Test]
    public function anExceptionFromASetHookPropagatesUnchanged(): void
    {
        try {
            (new ReflectionChangeApplier())->applyChanges(new Subscription('a@b.org'), ['email' => 'no-at-sign']);
            self::fail('the set hook rejected the value');
        } catch (\Throwable $thrown) {
            self::assertInstanceOf(InvalidEmail::class, $thrown, 'the object\'s own exception, not a wrapper');
            self::assertInstanceOf(Validation::class, $thrown);
        }
    }

    #[Test]
    public function aReadonlyDpoIsSanitizedByInspectingThenApplyingACopy(): void
    {
        $signUp = self::signUp();
        $rules = new RuleCollector();

        (new ReflectionPropertyInspector())->inspect($signUp, Sanitize::class, $rules);

        $properties = array_keys($rules->rules);
        $changes = array_combine($properties, array_map(
            static fn(string $property): mixed => self::sanitize($rules->values[$property], $rules->rules[$property]),
            $properties,
        ));

        $clean = (new ReflectionChangeApplier())->applyChanges($signUp, $changes);

        self::assertSame(['a@b.org', 'Walmir', 'acme', 30], [$clean->email, $clean->name(), $clean->tenant(), $clean->age]);
        self::assertSame(' A@B.org ', $signUp->email);
    }

    private static function signUp(): SignUp
    {
        return new SignUp(email: ' A@B.org ', name: ' Walmir ', age: 30);
    }

    /**
     * @param list<string> $rules
     */
    private static function sanitize(mixed $value, array $rules): mixed
    {
        return array_reduce(
            $rules,
            static fn(mixed $carry, string $rule): mixed => match (true) {
                $rule === 'trim' && is_string($carry) => trim($carry),
                $rule === 'lowercase' && is_string($carry) => strtolower($carry),
                default => $carry,
            },
            $value,
        );
    }
}
