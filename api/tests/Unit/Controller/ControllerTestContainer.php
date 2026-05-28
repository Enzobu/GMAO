<?php

namespace App\Tests\Unit\Controller;

use Psr\Container\ContainerInterface;

final class ControllerTestContainer implements ContainerInterface
{
    /** @param array<string, mixed> $services */
    public function __construct(private readonly array $services) {}

    public function get(string $id): mixed
    {
        return $this->services[$id] ?? throw new \RuntimeException(sprintf('Service "%s" not found.', $id));
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
