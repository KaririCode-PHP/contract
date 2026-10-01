<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Exception\Fixture;

use KaririCode\Contract\Exception\ContainerFailure;

/** Fixture: a component exception in the ContainerFailure family. Example: `throw new CircularDependency('...');` */
final class CircularDependency extends \LogicException implements ContainerFailure {}
