<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Exception\Runtime;
use RuntimeException;

/** Fixture: a change the applier cannot write, in the Runtime family. Example: `throw new ChangeRejected('...');` */
final class ChangeRejected extends RuntimeException implements Runtime {}
