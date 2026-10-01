<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\DI\Fixture;

/** Fixture service. Example: `new Greeting('hi')` */
final readonly class Greeting
{
    public function __construct(public string $text = 'hello') {}
}
