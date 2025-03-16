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
use F4\DB\WithTableReferenceCollection;

use F4\HookManager;

use F4\Config;

use function call_user_func_array;
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
            'doUpdateSet' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('do_update_set')) {
                    null => $this
                        ->append('DO UPDATE SET')
                        ->append((new AssignmentCollection(...$arguments))->setName('do_update_set')),
                    default => $existingNamedFragmentCollection
                        ->append(new AssignmentCollection(...$arguments))
                }),
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
            'group', 'groupBy' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('group_by')) {
                    null => $this
                        ->append('GROUP BY')
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...$arguments))->setName('group_by_collection')))->setName('group_by')),
                    default => $existingNamedFragmentCollection
                        ->findFragmentCollectionByName('group_by_collection')
                        ->append(new SimpleColumnReferenceCollection(...$arguments))
                }),
            'groupByAll' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('group_by')) {
                    null => $this
                        ->append('GROUP BY ALL')
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...$arguments))->setName('group_by_collection')))->setName('group_by')),
                    default => $existingNamedFragmentCollection
                        ->findFragmentCollectionByName('group_by_collection')
                        ->append(new SimpleColumnReferenceCollection(...$arguments))
                }),
            'groupByDistinct' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('group_by')) {
                    null => $this
                        ->append('GROUP BY DISTINCT')
                        ->append((new Parenthesize((new SimpleColumnReferenceCollection(...$arguments))->setName('group_by_collection')))->setName('group_by')),
                    default => $existingNamedFragmentCollection
                        ->findFragmentCollectionByName('group_by_collection')
                        ->append(new SimpleColumnReferenceCollection(...$arguments))
                }),
            'having' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('having')) {
                    null => match(empty($arguments)) {
                        true => null,
                        default => $this
                            ->append('HAVING')
                            ->append((new ConditionCollection(...$arguments))->setName('having'))
                    },
                    default => $existingNamedFragmentCollection
                        ->append(new ConditionCollection(...$arguments))
                }),
            'insert' => $this
                ->append('INSERT'),
            'intersect' => $this
                ->append('INTERSECT'),
            // TODO: add support for parenthesis via argumens to union() to control order of evaluation for multiple unions/intersects/excepts
            'intersectAll' => $this
                ->append('INTERSECT ALL'),
            'into' => $this
                ->append('INTO')
                ->append(new TableReferenceCollection(...$arguments)),
            'join' => $this
                ->append('JOIN')
                ->append(new TableReferenceCollection(...$arguments)),
            'leftJoin' => $this
                ->append('LEFT JOIN')
                ->append(new TableReferenceCollection(...$arguments)),
            'leftOuterJoin' => $this
                ->append('LEFT OUTER JOIN')
                ->append(new TableReferenceCollection(...$arguments)),
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
            'on' => $this
                ->append('ON')
                ->append(new ConditionCollection(...$arguments)),
            'onConflict' => $this
                ->append('ON CONFLICT')
                ->append(match (count($arguments) > 0) {
                        true => new Parenthesize(new SimpleColumnReferenceCollection($arguments)),
                        default => '',
                    }),
            'order', 'orderBy' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('order_by')) {
                    null => $this
                        ->append('ORDER BY')
                        ->append((new OrderCollection(...$arguments))->setName('order_by')),
                    default => $existingNamedFragmentCollection
                        ->append(new OrderCollection(...$arguments))
                }),
            'raw' => $this
                ->append(new FragmentCollection(...$arguments)),
            'returning' => $this
                ->append('RETURNING')
                ->append(new SimpleColumnReferenceCollection($arguments ?: '*')),
            'rightJoin' => $this
                ->append('RIGHT JOIN')
                ->append(new TableReferenceCollection(...$arguments)),
            'rightOuterJoin' => $this
                ->append('RIGHT OUTER JOIN')
                ->append(new TableReferenceCollection(...$arguments)),
            'select' => $this
                ->append('SELECT')
                ->append(new SelectExpressionCollection($arguments ?: '*')),
            'selectDistinct' => $this
                ->append('SELECT DISTINCT')
                ->append(new SelectExpressionCollection($arguments ?: '*')),
            'set' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('set')) {
                    null => $this
                        ->append('SET')
                        ->append((new AssignmentCollection(...$arguments))->setName('set')),
                    default => $existingNamedFragmentCollection
                        ->append(new AssignmentCollection(...$arguments))
                }),
            'update' => $this
                ->append('UPDATE')
                ->append(new TableReferenceCollection(...$arguments)),
            'union' => $this
                ->append('UNION'),
            // TODO: add support for parenthesis via argumens to union() to control order of evaluation for multiple unions/intersects/excepts
            'unionAll' => $this
                ->append('UNION ALL'),
            'using' => $this
                ->append('USING')
                ->append(new Parenthesize(new SimpleColumnReferenceCollection(...$arguments))),
            'values' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('values')) {
                    null => $this
                        ->append('VALUES')
                        ->append((new AssignmentCollection(...$arguments))->setName('values')),
                    default => $existingNamedFragmentCollection
                        ->append(new AssignmentCollection(...$arguments))
                }),
            'where' => (match ($existingNamedFragmentCollection = $this->findFragmentCollectionByName('where')) {
                    null => match(empty($arguments)) {
                        true => null,
                        default => $this
                            ->append('WHERE')
                            ->append((new ConditionCollection(...$arguments))->setName('where'))
                    },
                    default => $existingNamedFragmentCollection
                        ->append(new ConditionCollection(...$arguments))
                }),
            'with' => $this
                ->append('WITH')
                ->append(new WithTableReferenceCollection(...$arguments)),
            'withRecursive' => $this
                ->append('WITH RECURSIVE')
                ->append(new WithTableReferenceCollection(...$arguments)),
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
        return match(is_int($index)) {
            true => array_values($this->commit(stopAfter: 1)[0] ?? [])[$index] ?? null,
            default => ($this->commit(stopAfter: 1)[0][$index] ?? null)
        };
    }
}