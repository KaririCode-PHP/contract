<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

/** Fixture: an enum, which no reflection can instantiate. Example: `Status::Draft` */
enum Status
{
    case Draft;
}
