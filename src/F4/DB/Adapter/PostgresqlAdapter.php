<?php

declare(strict_types=1);

namespace F4\DB\Adapter;

use F4\Config;
use F4\DB\Adapter\AdapterInterface;
use F4\DB\PreparedStatement;

use ErrorException;
use InvalidArgumentException;
use Throwable;
use F4\DB\Exception\DuplicateColumnException;
use F4\DB\Exception\DuplicateFunctionException;
use F4\DB\Exception\DuplicateRecordException;
use F4\DB\Exception\DuplicateSchemaException;
use F4\DB\Exception\DuplicateTableException;
use F4\DB\Exception\Exception;
use F4\DB\Exception\InvalidTableDefinitionException;
use F4\DB\Exception\ParameterMismatchException;
use F4\DB\Exception\SyntaxErrorException;
use F4\DB\Exception\UnknownColumnException;
use F4\DB\Exception\UnknownFunctionException;
use F4\DB\Exception\UnknownTableException;

use PgSql\Connection;
use PgSql\Result;

use function array_map;
use function count;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_resource;
use function is_scalar;
use function mb_substr;
use function mb_trim;
use function pg_get_result;
use function pg_fetch_row;
use function pg_free_result;
use function pg_escape_identifier;
use function pg_escape_literal;
use function pg_field_name;
use function pg_field_type;
use function pg_last_error;
use function pg_query;
use function pg_result_error_field;
use function pg_send_query_params;
use function pg_send_query;
use function pg_set_client_encoding;
use function sprintf;

/**
 * 
 * Postgresql adapter class
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class PostgresqlAdapter implements AdapterInterface
{
    protected Connection $connection;

    public function __construct(?string $connectionString = null, int $connectionFlags = 0)
    {
        $connectionString = match (!empty($connectionString)) {
            true => $connectionString,
            default => match (mb_substr(mb_trim(Config::DB_HOST), 0, 1) === '/') {
                    true => sprintf("host='%s' dbname='%s' user='%s' password='%s'", Config::DB_HOST, Config::DB_NAME, Config::DB_USERNAME, Config::DB_PASSWORD),
                    default => sprintf("host='%s' port='%s' dbname='%s' user='%s' password='%s'", Config::DB_HOST, Config::DB_PORT, Config::DB_NAME, Config::DB_USERNAME, Config::DB_PASSWORD)
                }
        };
        $this->connection = $this->connect(connectionString: $connectionString, connectionFlags: $connectionFlags);
    }
    public function execute(PreparedStatement $statement, ?int $stopAfter = null): mixed
    {
        $query = $statement->query;
        // native booleans are passed as empty strings by default, which requires a workaround
        $parameters = array_map(function ($parameter) {
            return match (is_bool($parameter)) {
                true => $parameter ? 'true' : 'false',
                default => $parameter
            };
        }, $statement->parameters);

        if (!isset($this->connection)) {
            throw new ErrorException('Database connection not set', 500);
        }
        if (
            (count($parameters) && !pg_send_query_params($this->connection, $query, $parameters))
            ||
            (!count($parameters) && !pg_send_query($this->connection, $query))
        ) {
            throw new Exception(message: pg_last_error($this->connection), code: 500);
        }
        $result = pg_get_result($this->connection);
        if (!is_resource($result) && (!$result instanceof Result)) {
            throw new Exception(message: pg_last_error($this->connection), code: 500);
        }
        if (($code = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE)) !== null) {
            throw $this->convertErrorToException($code, pg_last_error($this->connection));
        }
        $data = [];
        while ((($stopAfter === null) || ($stopAfter > 0)) && ($row = pg_fetch_row($result)) !== FALSE) {
            if (is_array($row)) {
                $processedRow = [];
                for ($i = 0; $i < count($row); $i++) {
                    $processedRow[pg_field_name($result, $i)] = $this->castType($row[$i], pg_field_type($result, $i));
                }
            } else {
                $processedRow = $row;
            }
            $data[] = $processedRow;
            if (($stopAfter !== null) && (count($data) >= $stopAfter)) {
                break;
            }
        }
        pg_free_result($result);
        return $data;
    }
    public function enumerateParameters(int $index): string
    {
        return sprintf('$%d', $index);
    }
    protected function castType(mixed $value, string $type): mixed
    {
        if (is_array($value)) {
            foreach ($value as $i => $v) {
                $value[$i] = $this->castType($v, $type);
            }
        } else {
            if ($value === null) {
                return null;
            }
            switch ($type) {
                case 'smallint':
                case 'smallserial':
                case 'integer':
                case 'serial':
                case 'bigint':
                case 'bigserial':
                case 'int2':
                case 'int4':
                case 'int8':
                    $value = (int) $value;
                    break;
                case 'real':
                case 'double precision':
                    $value = (float) $value;
                    break;
                case 'json':
                case 'jsonb':
                    $value = json_decode(json: $value, associative: true, flags: JSON_THROW_ON_ERROR);
                    break;
                case 'boolean':
                case 'bool':
                    $value = match ($value) {
                        't' => true,
                        'f' => false,
                        default => null
                    };
                    break;
                // TODO: process psql arrays http://stackoverflow.com/questions/9169176/accessing-psql-array-directly-in-php
                //        case '_text':
                //                  if($parts='')
                //                    $value=$this->castType($parts, $type);
                //                  break;
                default:
            }
        }
        return $value;
    }

    protected function convertErrorToException(string $code, string $message): Throwable
    {
        return match ($code) {
            '08P01' => new ParameterMismatchException(message: $message),
            '22P02', '42601' => new SyntaxErrorException(message: $message),
            '23505' => new DuplicateRecordException(message: $message),
            '42703' => new UnknownColumnException(message: $message),
            '42723' => new DuplicateFunctionException(message: $message),
            '42883' => new UnknownFunctionException(message: $message),
            '42P01' => new UnknownTableException(message: $message),
            '42P06' => new DuplicateSchemaException(message: $message),
            '42P07' => new DuplicateTableException(message: $message),
            '42710', '42701' => new DuplicateColumnException(message: $message),
            '42P16' => new InvalidTableDefinitionException(message: $message),
            default => new Exception(message: sprintf("Database error %s, %s", $code, $message))
        };
    }

    public function connect(string $connectionString, int $connectionFlags = 0): Connection
    {
        $connection = null;
        try {
            $connection = match (Config::DB_PERSIST) {
                true => @pg_pconnect(connection_string: $connectionString, flags: $connectionFlags),
                default => @pg_connect(connection_string: $connectionString, flags: $connectionFlags)
            };
            if ((false === $connection) || (pg_connection_status(connection: $connection) !== PGSQL_CONNECTION_OK)) {
                throw new ErrorException('Database connection failed', 500);
            }
        } catch (ErrorException $e) {
            match (Config::DEBUG_MODE) {
                true => throw new ErrorException(message: sprintf("Database connection error: %s", pg_last_error($connection)), code: 500, previous: $e),
                default => throw $e
            };
        } catch (Exception $e) {
            throw $e;
        }
        try {
            if (pg_set_client_encoding(connection: $connection, encoding: Config::DB_CHARSET) !== 0) {
                throw new ErrorException(message: "failed-to-set-database-encoding", code: 500);
            }
            if (Config::TIMEZONE && !@pg_query(connection: $connection, query: sprintf('SET TIME ZONE %s', pg_escape_literal($connection, Config::TIMEZONE)))) {
                throw new ErrorException('Failed to set database timezone', 500);
            }
            if (Config::DB_SCHEMA && !@pg_query(connection: $connection, query: sprintf('SET "search_path" TO %s', pg_escape_literal($connection, Config::DB_SCHEMA)))) {
                throw new ErrorException('Failed to set database schema', 500);
            }
            if (Config::DB_APP_NAME && !@pg_query(connection: $connection, query: sprintf('SET "application_name" = %s', pg_escape_literal($connection, Config::DB_APP_NAME)))) {
                throw new ErrorException('Failed to set database application name', 500);
            }
        } catch (ErrorException $e) {
            match (Config::DEBUG_MODE) {
                true => throw new ErrorException(message: sprintf("Database error: %s", pg_last_error($connection)), code: 500, previous: $e),
                default => throw $e
            };
        }
        return $connection;
    }
    public function getEscapedValue(mixed $value): string
    {
        return match ($value === null) {
            true => 'NULL',
            default => match (is_bool($value)) {
                    true => $value ? 'TRUE' : 'FALSE',
                    default => match (is_int($value) || is_float($value)) {
                            true => (string) $value,
                            default => match ($value instanceof DateTime) {
                                    true => $value->format('Y-m-d H:i:s'),
                                    default => match (is_scalar($value)) {
                                            true => pg_escape_literal($this->connection, (string) $value),
                                            default => throw new InvalidArgumentException('Unsupported parameter type')
                                        }
                                }
                        }
                }
        };
    }

    public function getEscapedIdentifier(mixed $value): string
    {
        return pg_escape_identifier($this->connection, (string)$value);
    }

}