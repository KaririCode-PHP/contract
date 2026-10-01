<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception\Fixture;

use KaririCode\Contract\Exception\Protocol;

/** Fixture: a component exception in the Protocol family. Example: `throw new MalformedFrame('...');` */
final class MalformedFrame extends \UnexpectedValueException implements Protocol {}
