<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;
use F4\DB\FragmentCollectionInterface;
use F4\DB\FragmentInterface;
use F4\DB\PreparedStatement;

use function array_filter;
use function array_find;
use function array_map;
use function array_reduce;
use function implode;
use function is_array;
use function is_numeric;

/**
 * 
 * FragmentCollection is a class used to manage multiple fragments that can be collectively used as a semantically-meaningful query.
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class FragmentCollection implements FragmentCollectionInterface, FragmentInterface
{
    protected const string GLUE = ' ';
    protected array $fragments = [];
    protected ?string $name = null;
    protected ?string $prefix = null;

    public function __construct(...$arguments)
    {
        $this->addExpression($arguments);
    }
    public function append(FragmentInterface|string $fragment): static
    {
        $this->fragments[] = match ($fragment instanceof FragmentInterface) {
            true => $fragment,
            default => new Fragment($fragment)
        };
        return $this;
    }
    public function getFragments(): array
    {
        return $this->fragments;
    }
    public function getQuery(): string
    {
        return match (empty($query = implode(static::GLUE, array_filter(array_map(function (FragmentInterface $fragment): string {
            return $fragment->getQuery();
        }, $this->fragments))))) {
            true => '',
            default => match ($this->prefix) {
                    null => $query,
                    default => sprintf('%s %s', $this->prefix, $query)
                }
        };
    }
    public function getParameters(): array
    {
        return array_reduce($this->fragments, function ($result, FragmentInterface $fragment): array {
            return [...$result, ...$fragment->getParameters()];
        }, []);
    }
    public function setQuery(string $query, array $parameters = []): static
    {
        throw new InvalidArgumentException('Setting collection query and parameters directly is not supported, use append() instead');
    }
    public function withName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function resetName(): void
    {
        $this->name = null;
    }
    public function withPrefix(string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }
    public function getPreparedStatement(?callable $enumeratorFunction = null): PreparedStatement
    {
        $fragment = new Fragment(
            query: $this->getQuery(),
            parameters: $this->getParameters(),
        );
        return $fragment->getPreparedStatement($enumeratorFunction);
    }
    public function findFragmentCollectionByName(string $name): ?FragmentCollectionInterface
    {
        $fragment = array_find($this->getFragments(), function (FragmentInterface $fragment) use ($name) {
            return ($fragment instanceof FragmentCollection) && ($fragment->getName() === $name);
        });
        return $fragment;
    }
    public function addExpression(mixed $expression): void
    {
        if (is_array($expression)) {
            foreach ($expression as $key => $value) {
                if (is_numeric($key)) {
                    $this->addExpression($value);
                } else {
                    $this->append(new Fragment($key, $value));
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            $this->append($expression);
        } elseif (!empty($expression)) {
            $this->append(new Fragment($expression, []));
        }
    }
}
