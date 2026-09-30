<?php

declare(strict_types=1);

namespace KaririCode\Contract\Tests\Unit\Configuration\Fixture;

use KaririCode\Contract\Configuration\ConfigProvider;
use Override;

/**
 * Minimal ConfigProvider of a queue component.
 *
 * Example: `$builder->withProvider(new QueueConfigProvider());`
 */
final readonly class QueueConfigProvider implements ConfigProvider
{
    #[Override]
    public function namespace(): string
    {
        return 'queue';
    }

    #[Override]
    public function defaults(): array
    {
        return ['driver' => 'sync', 'workers' => 4];
    }

    #[Override]
    public function schema(): array
    {
        return ['driver' => 'string', 'workers' => 'int'];
    }
}
