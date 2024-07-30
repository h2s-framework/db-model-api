<?php

namespace Siarko\DbModelApi\Storage\ResultParser;

use Siarko\SqlCreator\DatabaseElement\Column;

interface ResultParserInterface
{
    public function canParse(string $query): bool;

    /**
     * @param array $resultData
     * @param string $query
     * @param Column[] $columnMeta
     * @return array
     */
    public function parse(array $resultData, string $query, array $columnMeta): array;

}