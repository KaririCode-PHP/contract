<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

/**
 * Fixture: a parent whose subclass redeclares one property (one storage slot)
 * and shadows a private one (two slots); it also has a static property.
 * Example: `final class Invoice extends Ledger`
 */
abstract class Ledger
{
    public static int $opened = 0;

    private string $note = 'ledger-private';

    public function __construct(protected readonly string $code = 'parent') {}

    public function ledgerNote(): string
    {
        return $this->note;
    }
}
