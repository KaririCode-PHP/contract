<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception\Fixture;

use KaririCode\Contract\Exception\Configuration;

/** Fixture: a component exception in the Configuration family. Example: `throw new InvalidQuota('...');` */
final class InvalidQuota extends \InvalidArgumentException implements Configuration {}
