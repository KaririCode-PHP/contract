<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception\Fixture;

use KaririCode\Contract\Exception\NotFound;

/** Fixture: a component exception in the NotFound family. Example: `throw new TenantMissing('...');` */
final class TenantMissing extends \OutOfBoundsException implements NotFound {}
