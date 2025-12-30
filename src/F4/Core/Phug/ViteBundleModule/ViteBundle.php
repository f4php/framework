<?php

declare(strict_types=1);

namespace F4\Core\Phug\ViteBundleModule;

use F4\Core\Phug\ViteBundleModule\PhugNodeInjectorInterface;
use Phug\Parser\NodeInterface;

use function array_map;

class ViteBundle implements PhugNodeInjectorInterface
{
    protected array $assets = [];
    public function __construct(
        public readonly string $name
    ) {}
    public function addAsset(PhugNodeInjectorInterface $asset): static {
        $this->assets[] = $asset;
        return $this;
    }
    public function injectNode(NodeInterface $containerNode): void {
        foreach($this->assets as $asset) {
            $asset->injectNode($containerNode);
        }
    }
}
