<?php

declare(strict_types=1);

namespace F4\Core;

trait PriorityAwareTrait
{
    public const int PRIORITY_CRITICAL = 20;
    public const int PRIORITY_HIGH = 10;
    public const int PRIORITY_NORMAL = 0;
    public const int PRIORITY_LOW = -10;
    protected int $priority = self::PRIORITY_NORMAL;

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

}
