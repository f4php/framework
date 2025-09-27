<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use Phug\{AbstractCompilerModule, CompilerEvent};
use Phug\Compiler\Event\NodeEvent;
use Exception;

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
                        if(!$localeAttribute = mb_trim($node->getAttribute('locale')??'', '\'\"')) {
                            throw new Exception('locale attribute is required for ftl:resource');
                        }
                        $srcAttribute = mb_trim($node->getAttribute('src')??'', '\'\"');
                        if ($locale === $localeAttribute) {
                            if($srcAttribute) {
                                $localizer->addResource(resource: $path.'/'.$srcAttribute, allowOverrides: true);
                            }
                            else if (($textNode = $node->getChildAt(0)) instanceof \Phug\Parser\Node\TextNode) {
                                $localizer->addFtl(string: $textNode->getValue(), allowOverrides: true);
                            }
                        }
                        $node = (new \Phug\Parser\Node\CommentNode)->hide();
                        $event->setNode($node);
                        // $node = (new \Phug\Parser\Node\ExpressionNode)->setValue(''); // todo: php code may be constructed here to add support for injecting ftl resources in cached templates
                        // $event->setNode($node);
                    }
                }
            }
        ];
    }
}