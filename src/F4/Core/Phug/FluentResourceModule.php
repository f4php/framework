<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use Phug\{AbstractCompilerModule, CompilerEvent};
use Phug\Compiler\Event\NodeEvent;

use function dirname;
use function mb_trim;

class FluentResourceModule extends AbstractCompilerModule
{
    protected const string ELEMENT_NAME = 'ftl:resource';
    public function getEventListeners(): array
    {
        return [
            CompilerEvent::NODE => function (NodeEvent $event): void {
                $node = $event->getNode();
                if ($node instanceof \Phug\Parser\Node\ElementNode) {
                    if ($node->getName() === self::ELEMENT_NAME) {
                        $f4 = $this->getContainer()->getOptions()->offsetGet('f4');
                        $localizer = $f4->getLocalizer();
                        $locale = $localizer->getLocale();
                        $path = dirname($this->getContainer()->getPath());
                        $nodeSrc = mb_trim($node->getAttribute('src'), '\'\"');
                        $nodeLocale = mb_trim($node->getAttribute('locale'), '\'\"');
                        if ($locale === $nodeLocale) {
                            $localizer->addresource($path.'/'.$nodeSrc, true);
                        }
                        $node = (new \Phug\Parser\Node\CommentNode())->hide();
                        $event->setNode($node);
                    }
                }
            }
        ];
    }
}