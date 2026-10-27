<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Inspection\Fixture;

use KaririCode\Contract\Exception\Runtime;
use RuntimeException;

/** Fixture: the inspector's own failure, in the Runtime family. Example: `throw new InspectionFailed('...');` */
final class InspectionFailed extends RuntimeException implements Runtime {}
