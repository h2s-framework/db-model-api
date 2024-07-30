<?php

namespace Siarko\DbModelApi\Storage\ResultParser;

use Siarko\SqlCreator\DatabaseElement\Column;
use Siarko\SqlCreator\Language\Tokens\Token;
use Siarko\Utils\ArrayManager;

class SelectResultParser implements ResultParserInterface
{

    public function __construct(
        protected readonly ArrayManager $arrayManager
    )
    {
    }

    /**
     * @param array $resultData
     * @param string $query
     * @param Column[] $columnMeta
     * @return array
     */
    public function parse(array $resultData, string $query, array $columnMeta): array
    {
        $result = [];
        foreach ($resultData as $dataRow) {
            $resultRow = [];
            foreach ($dataRow as $columnName => $columnValue) {
                foreach ($columnMeta[$columnName] as $tableName => $keys) {
                    $this->arrayManager->set([$tableName, $columnName],$resultRow, $columnValue);
                }
            }
            $result[] = $resultRow;
        }
        return $result;
    }

    public function canParse(string $query): bool
    {
        return str_starts_with(strtoupper($query), Token::SELECT->value);
    }
}