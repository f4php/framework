<?php

declare(strict_types=1);

namespace F4;

use BadMethodCallException;
use InvalidArgumentException;

use F4\DB\Adapter\AdapterInterface;
use F4\DB\AssignmentCollection;
use F4\DB\ConditionCollection;
use F4\DB\FragmentInterface;
use F4\DB\FragmentCollection;
use F4\DB\FragmentCollectionInterface;
use F4\DB\OrderCollection;
use F4\DB\Parenthesize;
use F4\DB\SelectExpressionCollection;
use F4\DB\SimpleColumnReferenceCollection;
use F4\DB\TableReferenceCollection;
use F4\DB\ValueExpressionCollection;
use F4\DB\WithTableReferenceCollection;

use F4\HookManager;

use F4\Config;

use function array_keys;
use function array_map;
use function array_values;
use function call_user_func_array;
use function is_array;
use function is_int;

/**
 * 
 * DB is entry point for building queries
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class DB extends FragmentCollection implements FragmentCollectionInterface, FragmentInterface
{
    protected AdapterInterface $adapter;

    public function __construct(?string $connectionString = null, string $adapterClass = Config::DB_ADAPTER_CLASS)
    {
        $this->adapter = new $adapterClass($connectionString);
    }

    public function __call(string $method, array $arguments): static
    {
        match ($method) {
            'delete' => $this
                ->append('DELETE'),
            'doNothing' => $this
                ->append('DO NOTHING'),
            'doUpdateSet' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('do_update_set')) {
                    null => $this
                        ->append((new AssignmentCollection(...$arguments))->withPrefix('DO UPDATE SET')->withName('do_update_set')),
                    default => $existingNamedFragmentCollection
                        ->append(new AssignmentCollection(...$arguments))
                },
            'dropTable' => $this
                ->append('DROP TABLE')
                ->append(new TableReferenceCollection(...$arguments)),
            'dropTableIfExists' => $this
                ->append('DROP TABLE IF EXISTS')
                ->append(new TableReferenceCollection(...$arguments)),
            'dropTableWithCascade' => $this
                ->append('DROP TABLE')
                ->append(new TableReferenceCollection(...$arguments))
                ->append('CASCADE'),
            'dropTableIfExistsWithCascade' => $this
                ->append('DROP TABLE IF EXISTS')
                ->append(new TableReferenceCollection(...$arguments))
                ->append('CASCADE'),
            'except' => $this
                ->append('EXCEPT'),
            // TODO: add support for parenthesis via argumens to union() to control order of evaluation for multiple unions/intersects/excepts
            'exceptAll' => $this
                ->append('EXCEPT ALL'),
            'from' => $this
                ->append('FROM')
                ->append(new TableReferenceCollection(...$arguments)),
            'fullOuterJoin' => $this
                ->append('FULL OUTER JOIN'),
            'group', 'groupBy' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('group_by')) {
                    null => $this
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...$arguments))->withName('group_by_collection')))->withPrefix('GROUP BY')->withName('group_by')),
                    default => $existingNamedFragmentCollection
                        ->findFragmentCollectionByName('group_by_collection')
                        ->append(new SimpleColumnReferenceCollection(...$arguments))
                },
            'groupByAll' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('group_by')) {
                    null => $this
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...$arguments))->withName('group_by_collection')))->withPrefix('GROUP BY ALL')->withName('group_by')),
                    default => $existingNamedFragmentCollection
                        ->findFragmentCollectionByName('group_by_collection')
                        ->append(new SimpleColumnReferenceCollection(...$arguments))
                },
            'groupByDistinct' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('group_by')) {
                    null => $this
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...$arguments))->withName('group_by_collection')))->withPrefix('GROUP BY DISTINCT')->withName('group_by')),
                    default => $existingNamedFragmentCollection
                        ->findFragmentCollectionByName('group_by_collection')
                        ->append(new SimpleColumnReferenceCollection(...$arguments))
                },
            'having' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('having')) {
                null => $this
                    ->append((new ConditionCollection(...$arguments))->withPrefix('HAVING')->withName('having')),
                default => array_map(function($argument) use ($existingNamedFragmentCollection): void {
                    $existingNamedFragmentCollection->addExpression($argument);
                }, $arguments)
            },
            'insert' => $this
                ->append('INSERT'),
            'intersect' => $this
                ->append('INTERSECT'),
            // TODO: add support for parenthesis via argumens to union() to control order of evaluation for multiple unions/intersects/excepts
            'intersectAll' => $this
                ->append('INTERSECT ALL'),
            'into' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('INTO')),
            'join' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('JOIN')),
            'leftJoin' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('LEFT JOIN')),
            'leftOuterJoin' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('LEFT OUTER JOIN')),
            'limit' => $this
                ->append(match (!isset($arguments[0]) || !is_int($arguments[0])) {
                        true => throw new InvalidArgumentException('Limit must have at least one integer argument'),
                        default => match (!isset($arguments[1]) || !is_int($arguments[1])) {
                                true => sprintf('LIMIT %d', $arguments[0]),
                                default => sprintf('LIMIT %d OFFSET %d', $arguments[0], $arguments[1])
                            },
                    }),
            'offset' => $this
                ->append(match (!isset($arguments[0]) || !is_int($arguments[0])) {
                        true => throw new InvalidArgumentException('Offset must have exactly one integer argument'),
                        default => sprintf('OFFSET %d', $arguments[0]),
                    }),
            'on' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('on')) {
                null => $this
                    ->append((new ConditionCollection(...$arguments))->withPrefix('ON')->withName('on')),
                default => array_map(function($argument) use ($existingNamedFragmentCollection): void {
                    $existingNamedFragmentCollection->addExpression($argument);
                }, $arguments)
            },
            'onConflict' => $this
                ->append('ON CONFLICT')
                ->append(match (count($arguments) > 0) {
                        true => new Parenthesize(new SimpleColumnReferenceCollection($arguments)),
                        default => '',
                    }),
            'order', 'orderBy' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('order_by')) {
                    null => $this
                        ->append((new OrderCollection(...$arguments))->withPrefix('ORDER BY')->withName('order_by')),
                    default => $existingNamedFragmentCollection
                        ->append(new OrderCollection(...$arguments))
                },
            'raw' => $this
                ->append(new FragmentCollection(...$arguments)),
            'returning' => $this
                ->append((new SimpleColumnReferenceCollection($arguments ?: '*'))->withPrefix('RETURNING')),
            'rightJoin' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('RIGHT JOIN')),
            'rightOuterJoin' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('RIGHT OUTER JOIN')),
            'select' => $this
                ->append((new SelectExpressionCollection($arguments ?: '*'))->withPrefix('SELECT')),
            'selectDistinct' => $this
                ->append((new SelectExpressionCollection($arguments ?: '*'))->withPrefix('SELECT DISTINCT')),
            'set' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('set')) {
                    null => $this
                        ->append((new AssignmentCollection(...$arguments))->withPrefix('SET')->withName('set')),
                    default => $existingNamedFragmentCollection
                        ->append(new AssignmentCollection(...$arguments))
                }),
            'update' => $this
                ->append((new TableReferenceCollection(...$arguments))->withPrefix('UPDATE')),
            'union' => $this
                ->append('UNION'),
            // TODO: add support for parenthesis via argumens to union() to control order of evaluation for multiple unions/intersects/excepts
            'unionAll' => $this
                ->append('UNION ALL'),
            'using' => $this
                ->append((new Parenthesize(new SimpleColumnReferenceCollection(...$arguments)))->withPrefix('USING')),
            'values' => array_map(function($argument) {
                is_array($argument) &&
                match (($existingFieldsFragmentCollection = $this->findFragmentCollectionByName('insert_fields')) && ($existingValuesFragmentCollection = $this->findFragmentCollectionByName('insert_values'))) {
                    false => $this
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...array_keys($argument)))->withName('insert_fields_collection')))->withName('insert_fields'))
                        ->append((new Parenthesize((new ValueExpressionCollection(...array_values($argument)))->withName('insert_values_collection')))->withPrefix('VALUES')->withName('insert_values')),
                    default => $existingFieldsFragmentCollection
                        ->findFragmentCollectionByName('insert_fields_collection')
                        ->append(new SimpleColumnReferenceCollection(...array_keys($argument)))
                    &&
                    $existingValuesFragmentCollection
                        ->findFragmentCollectionByName('insert_values_collection')
                        ->append(new ValueExpressionCollection(...array_values($argument)))
                };
            }, $arguments),
            'where' => match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('where')) {
                    null => $this
                        ->append((new ConditionCollection(...$arguments))->withPrefix('WHERE')->withName('where')),
                    default => array_map(function($argument) use ($existingNamedFragmentCollection): void {
                        $existingNamedFragmentCollection->addExpression($argument);
                    }, $arguments)
                },
            'with' => $this
                ->append((new WithTableReferenceCollection(...$arguments))->withPrefix('WITH')),
            'withRecursive' => $this
                ->append((new WithTableReferenceCollection(...$arguments))->withPrefix('WITH RECURSIVE')),
            default => throw new BadMethodCallException(message: "Unsupported method {$method}()")
        };
        return $this;
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        return match ($method) {
            'raw',
            'delete',
            'dropTable',
            'dropTableIfExists',
            'dropTableWithCascade',
            'dropTableIfExistsWithCascade',
            'insert',
            'select',
            'selectDistinct',
            'update',
            'with',
            'withRecursive'
            => call_user_func_array(callback: [new self(), $method], args: $arguments),
            default
            => throw new BadMethodCallException(message: "Unsupported method {$method}()")
        };
    }

    public function commit(?int $stopAfter = null): mixed
    {
        $preparedStatement = $this->getPreparedStatement($this->adapter->enumerateParameters(...));
        HookManager::triggerHook(HookManager::BEFORE_SQL_SUBMIT, ['statement' => $preparedStatement->query, 'parameters' => $preparedStatement->parameters]);
        $result = $this->adapter->execute(statement: $preparedStatement, stopAfter: $stopAfter);
        HookManager::triggerHook(HookManager::AFTER_SQL_SUBMIT, ['statement' => $preparedStatement->query, 'parameters' => $preparedStatement->parameters, 'result' => $result]);
        return $result;
    }
    public function asTable(): mixed
    {
        return $this->commit();
    }
    public function asRow(): mixed
    {
        return $this->commit(stopAfter: 1)[0] ?? null;
    }
    public function asValue(mixed $index = 0): mixed
    {
        return match (is_int($index)) {
            true => array_values($this->commit(stopAfter: 1)[0] ?? [])[$index] ?? null,
            default => ($this->commit(stopAfter: 1)[0][$index] ?? null)
        };
    }
}