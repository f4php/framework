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

class ViteLinkAsset implements PhugNodeInjectorInterface
{
    use SriAwareTrait;
    /**
     * Valid script attributes per HTML spec (excluding global/event attributes)
     */
    private const array VALID_ATTRIBUTES = [
        'as',
        'blocking',
        'crossorigin',
        'disabled',
        'fetchpriority',
        'hreflang',
        'imagesizes',
        'imagesrcset',
        'integrity',
        'media',
        'referrerpolicy',
        'rel',
        'sizes',
        'title',
        'type',
    ];
    public function __construct(
        protected string $href,
        // stylesheet is the default rel
        protected array $attributes = [
            'rel' => 'stylesheet',
        ],
        protected bool|array $withSri = false,
        protected ?NodeInterface $template = null,
    ) {
        if ($this->attributes['href'] ?? false) {
            throw new InvalidArgumentException(
                'Link "href" cannot be set in attributes array. Use the required $href constructor parameter instead.',
            );
        }
        if ($invalidAttributes = array_diff(array_keys($this->attributes), self::VALID_ATTRIBUTES)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid <link> attribute(s): %s. Allowed attributes: %s.',
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
        $node = $this->template ?: new ElementNode()->setName('link');
        // template overrides all attributes except href and integrity
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
                ->setName('href')
                ->setValue(sprintf('"%s"', $this->href)),
        );
        if($this->withSri !== false) {
            $path = Loader::getPublicPath().$this->href;
            $node->getAttributes()->offsetSet(
                new AttributeNode()
                    ->setName('integrity')
                    ->setValue(sprintf('"%s"', $this->generateSri($path, $this->withSri))),
            );
        }
        $containerNode->appendChild($node);
    }
}
