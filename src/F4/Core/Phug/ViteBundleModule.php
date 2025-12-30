<?php

declare(strict_types=1);

namespace F4\Core\Phug;

use ErrorException;
use F4\Config;
use F4\Core\AssetManifestAwareTrait;
use F4\Core\Phug\ViteBundleModule\{ViteBundle, ViteScriptAsset, ViteLinkAsset};
use Phug\{AbstractCompilerModule, CompilerEvent};
use Phug\Compiler\Event\NodeEvent;
use Phug\Parser\Node\{ConditionalNode, ElementNode};

use function array_reduce;
use function array_unique;
use function iterator_to_array;
use function mb_trim;

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
                if ($node instanceof ElementNode && $node->getName() === self::ELEMENT_NAME) {
                    // ConditionalNode is a convenient insertion point type that may contain arbitrary number of elements as children
                    $containerNode = new ConditionalNode();
                    $bundleName = mb_trim($node->getAttribute('name'), '\'\"') ?: self::DEFAULT_BUNDLE_NAME;
                    $viteBundle = new ViteBundle($bundleName);
                    if (Config::DEBUG_MODE && isset($_SERVER[self::VITE_DEVSERVER_HEADER])) {
                        if (!$this->viteClientCodeAdded) {
                            $viteBundle->addAsset(new ViteScriptAsset(
                                src: '/@vite/client',
                            ));
                            $this->viteClientCodeAdded = true;
                        }
                        $viteBundle->addAsset(new ViteScriptAsset(
                            src: '/@id/__x00__' . self::ENTRY_POINT_NAME_PREFIX . $bundleName,
                        ));
                    } else {
                        $preload = array_reduce(
                        array: iterator_to_array($node->getAttributes()),
                            callback: fn(bool $result, $attribute): bool =>
                            $result || ($attribute->getName() === 'preload'),
                            initial: false,
                        );
                        $scriptNodeTemplate = null;
                        $linkNodeTemplate = null;
                        foreach ($node->getChildren() as $childNode) {
                            if ($childNode instanceof ElementNode) {
                                if ($childNode->getName() === 'script') {
                                    $scriptNodeTemplate = $childNode;
                                } else if ($childNode->getName() === 'link') {
                                    $linkNodeTemplate = $childNode;
                                }
                            }
                        }
                        if ($scriptSrc = self::getManifestData(entryPoint: self::ENTRY_POINT_NAME_PREFIX . $bundleName, property: 'file')) {
                            if ($preload) {
                                $viteBundle->addAsset(new ViteLinkAsset(
                                    href: $scriptSrc,
                                    attributes: [
                                        'rel' => 'preload',
                                        'as' => 'script',
                                        'crossorigin' => 'anonymous',
                                    ],
                                ));
                            }
                            $viteBundle->addAsset(new ViteScriptAsset(
                                src: $scriptSrc,
                                template: $scriptNodeTemplate,
                            ));
                        }
                        if ($linkHrefs = self::collectStylesheetHrefs(entryPoint: self::ENTRY_POINT_NAME_PREFIX . $bundleName)) {
                            foreach ($linkHrefs as $linkHref) {
                                if ($preload) {
                                    $viteBundle->addAsset(new ViteLinkAsset(
                                        href: $linkHref,
                                        attributes: [
                                            'rel' => 'preload',
                                            'as' => 'style',
                                        ],
                                    ));
                                }
                                $viteBundle->addAsset(new ViteLinkAsset(
                                    href: $linkHref,
                                    template: $linkNodeTemplate,
                                ));
                            }
                        }
                        if (self::FALLBACK_CSS_BUNDLE_NAME && !$this->fallbackCssBundleAdded && ($linkHref = self::getManifestData(entryPoint: self::FALLBACK_CSS_BUNDLE_NAME, property: 'file'))) {
                            $viteBundle->addAsset(new ViteLinkAsset(
                                href: $linkHref,
                                template: $linkNodeTemplate,
                            ));
                            $this->fallbackCssBundleAdded = true;
                        }
                    }
                    $viteBundle->injectNode($containerNode);
                    $event->setNode($containerNode);
                }
            }
        ];
    }
    protected function collectStylesheetHrefs(string $entryPoint, int $recursionLock = 64): array
    {
        if ($recursionLock <= 0) {
            throw new ErrorException('Recursion too deep when collecting stylesheet data from vite manifest file');
        }
        $references = [
            $entryPoint => (array) self::getManifestData(entryPoint: $entryPoint, property: 'css')
        ];
        if ($imports = (array) self::getManifestData(entryPoint: $entryPoint, property: 'imports', prependPath: false)) {
            foreach ($imports as $importEntryPoint) {
                $references[$importEntryPoint] = [
                    ...$references[$importEntryPoint] ?? [],
                    ...$this->collectStylesheetHrefs(entryPoint: $importEntryPoint, recursionLock: $recursionLock - 1),
                ];
            }
        }
        return array_unique(array_reduce(
            array: $references,
            callback: fn(array $references, array $entryPointReferences): array => [
                ...$references,
                ...$entryPointReferences,
            ],
            initial: [],
        ));
    }
}