<?php

declare(strict_types=1);

namespace F4\Core\Phug\ViteBundleModule;

use InvalidArgumentException;
use F4\Core\Phug\ViteBundleModule\{PhugNodeInjectorInterface, SriAwareTrait};
use F4\Loader;
use Phug\Parser\Node\{AttributeNode, ElementNode};
use Phug\Parser\NodeInterface;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

class ViteScriptAsset implements PhugNodeInjectorInterface
{
    use SriAwareTrait;
    /**
     * Valid script attributes per HTML spec (excluding global/event attributes)
     */
    private const array VALID_ATTRIBUTES = [
        'async',
        'attributionsrc',
        'crossorigin',
        'defer',
        'fetchpriority',
        'integrity',
        'nomodule',
        'nonce',
        'referrerpolicy',
        'type',
    ];
    public function __construct(
        protected string $src,
        // module is the default type for vite-bundled script resources
        protected array $attributes = [
            'type' => 'module',
        ],
        protected bool|array $withSri = false,
        protected ?NodeInterface $template = null,
    ) {
        if ($this->attributes['src'] ?? false) {
            throw new InvalidArgumentException(
                'Script "src" cannot be set in attributes array. Use the required $src constructor parameter instead.',
            );
        }
        if ($invalidAttributes = array_diff(array_keys($this->attributes), self::VALID_ATTRIBUTES)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid <script> attribute(s): %s. Allowed attributes: %s.',
                implode(separator: ', ', array: $invalidAttributes),
                implode(separator: ', ', array: self::VALID_ATTRIBUTES),
            ));
        }
        if ($template) {
            $this->template = clone $template;
        }
        if ($this->withSri === true) {
            $this->withSri = [self::DEFAULT_SRI_ALGORITHM];
        }
    }
    public function injectNode(NodeInterface $containerNode): void
    {
        $node = $this->template ?: new ElementNode()->setName('script');
        // template overrides all attributes except src and integrity
        if(!$this->template) {
            foreach ($this->attributes as $name => $value) {
                $node->getAttributes()->offsetSet(
                    new AttributeNode()
                        ->setName($name)
                        ->setValue(sprintf('"%s"', $value)),
                );
            }
        }
        $node->getAttributes()->offsetSet(
            new AttributeNode()
            ->setName('src')
            ->setValue(sprintf('"%s"', $this->src)),
        );
        if($this->withSri !== false) {
            $path = Loader::getPublicPath().$this->src;
            $node->getAttributes()->offsetSet(
                new AttributeNode()
                    ->setName('integrity')
                    ->setValue(sprintf('"%s"', $this->generateSri($path, $this->withSri))),
            );
        }
        $containerNode->appendChild($node);
    }
}
