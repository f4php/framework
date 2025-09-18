<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use F4\Config;
use F4\Core\AssetManifestAwareTrait;
use Phug\{AbstractCompilerModule, CompilerEvent};
use Phug\Compiler\Event\NodeEvent;

use function array_reduce;
use function iterator_to_array;
use function is_array;
use function mb_strlen;
use function mb_substr;
use function mb_trim;
use function sprintf;

class ViteBundleModule extends AbstractCompilerModule
{
    use AssetManifestAwareTrait;

    protected const string ELEMENT_NAME = 'vite:bundle';
    protected const string ENTRY_POINT_NAME_PREFIX = 'virtual:f4/';
    protected const string DEFAULT_BUNDLE_NAME = 'default';
    protected const string FALLBACK_CSS_BUNDLE_NAME = Config::TEMPLATE_PUG_FALLBACK_CSS_BUNDLE_NAME;
    protected const string VITE_DEVSERVER_HEADER = 'HTTP_X_VITE_DEVSERVER';
    protected bool $viteClientCodeAdded = false;
    protected bool $fallbackCssBundleAdded = false;
    public function getEventListeners(): array
    {
        return [
            CompilerEvent::NODE => function (NodeEvent $event): void {
                $node = $event->getNode();
                if ($node instanceof \Phug\Parser\Node\ElementNode) {
                    if (mb_substr($node->getName() ?? '', 0, mb_strlen(self::ELEMENT_NAME)) === self::ELEMENT_NAME) {
                        // ConditionalNode is a convenient insertion point type that may contain arbitrary number of elements as children
                        $containerNode = new \Phug\Parser\Node\ConditionalNode;
                        $bundleName = mb_trim($node->getAttribute('name'), '\'\"') ?: self::DEFAULT_BUNDLE_NAME;
                        if (Config::DEBUG_MODE && isset($_SERVER[self::VITE_DEVSERVER_HEADER])) {
                            $scriptNode = new \Phug\Parser\Node\ElementNode();
                            $scriptNode->setName('script');
                            if (!$this->viteClientCodeAdded) {
                                $viteClientScriptNode = clone $scriptNode;
                                $viteClientScriptNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('src')->setValue('"/@vite/client"'));
                                $viteClientScriptNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('type')->setValue('"module"'));
                                $containerNode->appendChild($viteClientScriptNode);
                                $this->viteClientCodeAdded = true;
                            }
                            $bundleClientScriptNode = clone $scriptNode;
                            $bundleClientScriptNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('src')->setValue(sprintf('"/@id/__x00__%s%s"', self::ENTRY_POINT_NAME_PREFIX, $bundleName)));
                            $bundleClientScriptNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('type')->setValue('"module"'));
                            $containerNode->appendChild($bundleClientScriptNode);
                        } else {
                            $preload = array_reduce(iterator_to_array($node->getAttributes()), function($result, $attribute) { 
                                return $result || ($attribute->getName() === 'preload');
                            }, false);
                            $scriptNodeTemplate = new \Phug\Parser\Node\ElementNode();
                            $scriptNodeTemplate->setName('script');
                            $linkNodeTemplate = new \Phug\Parser\Node\ElementNode();
                            $linkNodeTemplate->setName('link');
                            foreach ($node->getChildren() as $childNode) {
                                if ($childNode instanceof \Phug\Parser\Node\ElementNode) {
                                    if ($childNode->getName() === 'script') {
                                        $scriptNodeTemplate = $childNode;
                                    } else if ($childNode->getName() === 'link') {
                                        $linkNodeTemplate = $childNode;
                                    }
                                }
                            }
                            if ($scriptSrc = self::getManifestData(entryPoint: self::ENTRY_POINT_NAME_PREFIX . $bundleName, property: 'file')) {
                                if($preload) {
                                    $preloadNode = new \Phug\Parser\Node\ElementNode();
                                    $preloadNode->setName('link');
                                    $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('href')->setValue(sprintf('"%s"', $scriptSrc)));
                                    $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('rel')->setValue('"preload"'));
                                    $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('as')->setValue('"script"'));
                                    $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('crossorigin')->setValue('"anonymous"'));
                                    $containerNode->appendChild($preloadNode);
                                }
                                $scriptNode = clone $scriptNodeTemplate;
                                $scriptNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('src')->setValue(sprintf('"%s"', $scriptSrc)));
                                if (!$scriptNode->getAttribute('type')) {
                                    $scriptNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('type')->setValue('"module"'));
                                }
                                $containerNode->appendChild($scriptNode);
                            }
                            if (is_array($linkHrefs = self::getManifestData(entryPoint: self::ENTRY_POINT_NAME_PREFIX . $bundleName, property: 'css'))) {
                                foreach ($linkHrefs as $linkHref) {
                                    if($preload) {
                                        $preloadNode = new \Phug\Parser\Node\ElementNode();
                                        $preloadNode->setName('link');
                                        $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('href')->setValue(sprintf('"%s"', $linkHref)));
                                        $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('rel')->setValue('"preload"'));
                                        $preloadNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('as')->setValue('"style"'));
                                        $containerNode->appendChild($preloadNode);
                                    }
                                    $linkNode = clone $linkNodeTemplate;
                                    $linkNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('href')->setValue(sprintf('"%s"', $linkHref)));
                                    if (!$linkNode->getAttribute('rel')) {
                                        $linkNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('rel')->setValue('"stylesheet"'));
                                    }
                                    $containerNode->appendChild($linkNode);
                                }
                            } else if ($linkHrefs) {
                                $linkNode = clone $linkNodeTemplate;
                                $linkNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('href')->setValue(sprintf('"%s"', $linkHrefs)));
                                $linkNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('rel')->setValue('"stylesheet"'));
                                $containerNode->appendChild($linkNode);
                            }
                            if (self::FALLBACK_CSS_BUNDLE_NAME && !$this->fallbackCssBundleAdded && ($linkHref = self::getManifestData(entryPoint: self::FALLBACK_CSS_BUNDLE_NAME, property: 'file'))) {
                                $linkNode = clone $linkNodeTemplate;
                                $linkNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('href')->setValue(sprintf('"%s"', $linkHref)));
                                $linkNode->getAttributes()->attach(new \Phug\Parser\Node\AttributeNode()->setName('rel')->setValue('"stylesheet"'));
                                $containerNode->appendChild($linkNode);
                                $this->fallbackCssBundleAdded = true;
                            }
                        }
                        $event->setNode($containerNode);
                    }
                }
            }
        ];
    }
}