<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;
use F4\DB\FragmentInterface;
use F4\DB\PreparedStatement;

use function array_map;
use function array_reduce;
use function implode;

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
        return implode(static::GLUE, array_map(function (FragmentInterface $fragment): string {
            return $fragment->getQuery();
        }, $this->fragments));
    }
    public function getParameters(): array
    {
        return array_reduce($this->fragments, function ($result, FragmentInterface $fragment): array {
            return [...$result, ...$fragment->getParameters()];
        }, []);
    }
    public function getPreparedStatement(?callable $enumeratorFunction = null): PreparedStatement
    {
        $fragment = new Fragment(
            query: $this->getQuery(),
            parameters: $this->getParameters(),
        );
        return $fragment->getPreparedStatement($enumeratorFunction);
    }
    public function setQuery(string $query, array $parameters = []): static
    {
        throw new InvalidArgumentException('Setting collection query and parameters directly is not supported, use append() instead');
    }
    protected function addExpression(mixed $expression): void
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
        } else {
            $this->append(new Fragment($expression, []));
        }
    }

}

