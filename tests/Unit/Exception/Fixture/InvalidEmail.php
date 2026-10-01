<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception\Fixture;

use KaririCode\Contract\Exception\Validation;

/** Fixture: a component exception in the Validation family. Example: `throw new InvalidEmail('...');` */
final class InvalidEmail extends \DomainException implements Validation {}
