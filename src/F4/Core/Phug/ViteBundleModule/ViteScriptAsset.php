<?php

declare(strict_types=1);

namespace F4\Core\Phug\ViteBundleModule;

use InvalidArgumentException;
use F4\Core\Phug\ViteBundleModule\PhugNodeInjectorInterface;
use Phug\Parser\Node\{AttributeNode, ElementNode};
use Phug\Parser\NodeInterface;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

class ViteScriptAsset implements PhugNodeInjectorInterface
{
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
    }
    public function injectNode(NodeInterface $containerNode): void
    {
        $node = $this->template ?: new ElementNode()->setName('script');
        // template overrides all attributes except src
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
        $containerNode->appendChild($node);
    }
}
