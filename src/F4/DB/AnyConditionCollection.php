<?php

declare(strict_types=1);

namespace F4\DB;

use F4\DB\ConditionCollection;

use function sprintf;

/**
 * 
 * AnyConditionCollection is an abstraction of sql expressions allowed inside a "WHERE" part of a statement but with OR as a glue
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class AnyConditionCollection extends ConditionCollection
{
    protected const string GLUE = ' OR ';
    public function getQuery(): string
    {
        return sprintf('(%s)', parent::getQuery());
    }
    static public function of(...$arguments): ConditionCollection
    {
        $instance = new self(...$arguments);
        return $instance;
    }

}

