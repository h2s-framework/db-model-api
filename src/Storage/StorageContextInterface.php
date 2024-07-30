<?php

namespace Siarko\DbModelApi\Storage;

use Siarko\SqlCreator\BasicQuery;
use Siarko\SqlCreator\ConditionedQuery;

interface StorageContextInterface
{

    /**
     * @return void
     */
    function connect(): void;

    /**
     * @return string get last connection error
     */
    function getError(): string;

    /**
     * @param string|BasicQuery $sql
     * @param array $bind
     * @return StorageQueryResultInterface
     */
    function query(string|BasicQuery $sql, array $bind): StorageQueryResultInterface;

    /**
     * @return bool
     */
    function beginTransaction(): bool;

    /**
     * @return bool
     */
    function commitTransaction(): bool;

    /**
     * @return bool
     */
    function rollbackTransaction(): bool;
}