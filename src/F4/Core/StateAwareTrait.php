<?php

declare(strict_types=1);

namespace F4\Core;

trait StateAwareTrait
{
    protected $state = null;
    public function setState($key, $value): static {
        $this->state[$key] = $value;
        return $this;
    }
    public function getState(?string $key = null): mixed {
        return match($key !== null) {
            true => $this->state[$key] ?? null,
            default => $this->state
        };
    }
}