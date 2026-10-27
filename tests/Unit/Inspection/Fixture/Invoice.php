<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

/**
 * Fixture: redeclares Ledger::$code (the same storage slot) and declares a
 * public $note beside Ledger's private one (two slots). The parent
 * constructor is not called: the redeclared promotion initializes the slot.
 * Example: `new Invoice()`
 */
final class Invoice extends Ledger
{
    public string $note = 'invoice-public';

    public function __construct(protected readonly string $code = 'INV-1') {}

    public function code(): string
    {
        return $this->code;
    }
}
