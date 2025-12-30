<?php

declare(strict_types=1);

namespace F4\Core\Phug\ViteBundleModule;

use Phug\Parser\NodeInterface;

interface PhugNodeInjectorInterface
{
    public function injectNode(NodeInterface $containerNode): void;
}
