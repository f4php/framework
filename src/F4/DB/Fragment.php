<?php

declare(strict_types=1);

namespace F4\DB;

use Composer\Pcre\Preg;
use InvalidArgumentException;
use F4\DB\FragmentInterface;
use F4\DB\PreparedStatement;

use function array_map;
use function array_shift;
use function count;
use function implode;
use function is_array;
use function preg_quote;

/**
 * 
 * Fragment is an abstract class that contains a piece of SQL query with optional parameters and works as a foundation for all other query 
 * builder classes.
 *
 * The main feature of Fragment is parameter substitution, which uses several supported placeholder types to simplify complex query building
 * using native php data types.
 * 
 * It is not expected to have any semantic meaning SQL-wise.
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class Fragment implements FragmentInterface
{
    protected string $query;
    protected array $parameters = [];
    public const string SINGLE_PARAMETER_PLACEHOLDER = '{#}';
    public const string COMMA_PARAMETER_PLACEHOLDER = '{#,...#}';
    public const string SUBQUERY_PARAMETER_PLACEHOLDER = '{#::#}';

    public function __construct(?string $query=null, array $parameters = []) {
        if($query !== null) {
            $this->setQuery(query: $query, parameters: $parameters);
        }
    }
    public function setQuery(string $query, array $parameters = []): static
    {
        $patterns = $this->extractPlaceholders($query);
        if (count($patterns) !== count($parameters)) {
            throw new InvalidArgumentException('Parameter mismatch, expected: '.count($patterns).', received: '.count($parameters));
        }
        foreach ($patterns as $index => $pattern) {
            if (($pattern === self::SINGLE_PARAMETER_PLACEHOLDER) && (!isset($parameters[$index]) || !is_scalar($parameters[$index]))) {
                throw new InvalidArgumentException('Only scalars are supported for '.self::SINGLE_PARAMETER_PLACEHOLDER);
            }
            if (($pattern === self::COMMA_PARAMETER_PLACEHOLDER) && (!isset($parameters[$index]) || !is_array($parameters[$index]))) {
                throw new InvalidArgumentException('Only arrays are supported for '.self::COMMA_PARAMETER_PLACEHOLDER);
            }
            if (($pattern === self::SUBQUERY_PARAMETER_PLACEHOLDER) && (!isset($parameters[$index]) || !($parameters[$index] instanceof FragmentInterface))) {
                throw new InvalidArgumentException('Only DB objects are supported for '.self::SUBQUERY_PARAMETER_PLACEHOLDER);
            }
        }
        $this->query = $query;
        $this->parameters = $parameters;
        return $this;
    }
    public function getQuery(): string
    {
        return $this->query;
    }
    public function getParameters(): array
    {
        return $this->parameters;
    }
    public function getPreparedStatement(?callable $enumeratorCallback = null): PreparedStatement {
        /**
         * This is the default parameter enumerator for pg_sql, which converts every single parameter placeholder, 
         * or {#}, into $1, $2, $3 etc.
         * 
         * Other databases or drivers may use a different convention for prepared statement parameters,
         * in those situations $enumeratorCallback could be supplied to provide an alternative
         */ 
        $enumeratorCallback ??= function($index): string {
            return sprintf('$%d', $index);
        };
        [$query, $parameters] = $this->unpackComplexPlaceholders(query: $this->query, parameters: $this->parameters);
        $index = 1;
        $query = Preg::replaceCallback("/(".preg_quote(self::SINGLE_PARAMETER_PLACEHOLDER, '/').")/u", function () use (&$index, $enumeratorCallback) {
            return $enumeratorCallback($index++);
        }, $query);
        return new PreparedStatement(query: $query, parameters: $parameters);
    }

    protected function getPlaceholderRegExp(): string {
        return implode('|', array_map(function($pattern) {
            return preg_quote($pattern, '/');
        }, [
            self::SINGLE_PARAMETER_PLACEHOLDER,
            self::COMMA_PARAMETER_PLACEHOLDER,
            self::SUBQUERY_PARAMETER_PLACEHOLDER
        ]));
    }
    protected function extractPlaceholders(string $query): array {
        $regExpPattern = $this->getPlaceholderRegExp();
        return match(Preg::matchAll(pattern: "/({$regExpPattern})/u", subject: $query, matches: $matches)) {
            false => [],
            default => $matches[1] ?? []
        };
    }
    protected function unpackComplexPlaceholders(string $query, array $parameters): array {
        $regExpPattern = $this->getPlaceholderRegExp();
        $unpackedParameters = [];
        $unpackedQuery = Preg::replaceCallback("/({$regExpPattern})/u", function ($matches) use (&$parameters, &$unpackedParameters) {
            $pattern = $matches[1];
            if ($pattern === self::COMMA_PARAMETER_PLACEHOLDER) {
                return implode(',', array_map(function($value) use (&$unpackedParameters) {
                    $unpackedParameters[] = $value;
                    return self::SINGLE_PARAMETER_PLACEHOLDER;
                }, array_shift($parameters)));
            }
            elseif($pattern === self::SUBQUERY_PARAMETER_PLACEHOLDER) {
                $fragment = array_shift($parameters);
                [$subQuery, $subParameters] = $this->unpackComplexPlaceholders($fragment->getQuery(), $fragment->getParameters());
                $unpackedParameters = [...$unpackedParameters, ...$subParameters];
                return $subQuery;
            }
            else {
                $unpackedParameters[] = array_shift($parameters);
                return self::SINGLE_PARAMETER_PLACEHOLDER;
            };
        }, $query);
        return [$unpackedQuery, $unpackedParameters];
    }
}

