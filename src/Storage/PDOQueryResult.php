<?php

namespace Siarko\DbModelApi\Storage;

use PDO;
use Siarko\DbModelApi\Storage\ResultParser\ResultParserInterface;
use Siarko\SqlCreator\DatabaseElement\KeyType;

class PDOQueryResult implements StorageQueryResultInterface
{

    private array $fetchData = [];

    /**
     * @param \PDOStatement $statement
     * @param ResultParserInterface[] $resultParsers
     */
    public function __construct(
        private readonly \PDOStatement $statement,
        private readonly array $resultParsers = []
    )
    {
    }


    public function count(): int
    {
        return $this->statement->rowCount();
    }

    public function fetchAll(): array
    {
        if(count($this->fetchData) == 0){
            $data = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->fetchData = $this->runParsers($data, $this->statement->queryString);
        }
        return $this->fetchData;
    }

    /**
     * Run parsers on result data
     * @param array $resultData
     * @param string $query
     * @return array
     */
    protected function runParsers(array $resultData, string $query): array
    {
        foreach ($this->resultParsers as $resultParser) {
            if($resultParser->canParse($query)){
                return $resultParser->parse($resultData, $query, $this->getColumnsMetaOrigins());
            }
        }
        return $resultData;
    }

    /**
     * @return array
     */
    protected function getColumnsMetaOrigins(): array
    {
        $result = [];
        for($i = 0; $i < $this->statement->columnCount(); $i++){
            $meta = $this->statement->getColumnMeta($i);
            $name = $meta['name'];
            $table = $meta['table'];
            if(!array_key_exists($name, $result)){$result[$name] = [];}
            $result[$name][$table] = [];
            if(in_array('primary_key', $meta['flags'])){
                $result[$name][$table][] = KeyType::PRIMARY->name;
            }
            if(in_array('multiple_key', $meta['flags'])){
                $result[$name][$table][] = KeyType::FOREIGN->name;
            }
        }
        return $result;
    }

    public function getError(): ?array
    {
        $info = $this->statement->errorInfo();
        if($info[2] != null){
            return [
                'message' => $info[2],
                'code' => $info[0]
            ];
        }
        return null;
    }

    public function getNativeObject(): mixed
    {
        return $this->statement;
    }
}