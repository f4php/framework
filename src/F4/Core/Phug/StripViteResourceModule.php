<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use Phug\AbstractCompilerModule;
use Phug\Compiler\Event\NodeEvent;
use Phug\CompilerEvent;

class StripViteResourceModule extends AbstractCompilerModule
{
    protected const string ELEMENT_NAME = 'vite:resource';

    public function getEventListeners(): array
    {
        return [
            CompilerEvent::NODE => function (NodeEvent $event) {
                $node = $event->getNode();
                if ($node instanceof \Phug\Parser\Node\ElementNode) {
                    if ($node->getName() === self::ELEMENT_NAME) {
                        $node = (new \Phug\Parser\Node\CommentNode())->hide();
                        $event->setNode($node);
                    }
                }
            }
        ];
    }
}