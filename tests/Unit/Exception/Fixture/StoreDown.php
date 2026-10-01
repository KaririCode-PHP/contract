<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception\Fixture;

use KaririCode\Contract\Exception\Runtime;

/** Fixture: a component exception in the Runtime family. Example: `throw new StoreDown('...');` */
final class StoreDown extends \RuntimeException implements Runtime {}
