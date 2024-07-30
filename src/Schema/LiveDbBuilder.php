<?php

namespace Siarko\DbModelApi\Schema;

use Siarko\DbModelApi\Storage\StorageContextInterface;
use Siarko\DbModelApi\Storage\StorageCredentialProviderInterface;
use Siarko\SqlCreator\DatabaseElement\AssocParser\Column\ColumnParserInterface;
use Siarko\SqlCreator\DatabaseElement\AssocParser\Column\Key\ColumnKeyParserInterface;
use Siarko\SqlCreator\DatabaseElement\Table;
use Siarko\SqlCreator\DatabaseElement\TableFactory;
use Siarko\SqlCreator\Sql;

class LiveDbBuilder implements BuilderInterface
{

    public function __construct(
        private readonly StorageContextInterface $informationSchema,
        private readonly StorageCredentialProviderInterface $credentialProvider,
        private readonly ColumnParserInterface $columnParser,
        private readonly ColumnKeyParserInterface $columnKeyParser,
        protected readonly TableFactory $tableFactory
    )
    {
    }

    protected function getTargetDb(): string
    {
        return $this->credentialProvider->getDatabase();
    }

    public function build(): array
    {
        $structure = $this->fetchStructureData();
        $keys = $this->fetchKeyData();
        $structureData = $this->parseStructureData($structure);
        return $this->addKeyData($structureData, $keys);
    }

    protected function fetchStructureData(): array
    {
        return $this->informationSchema->query(
            Sql::select([
                ColumnParserInterface::COLUMN_TABLE_NAME,
                ColumnParserInterface::COLUMN_COLUMN_NAME,
                ColumnParserInterface::COLUMN_DEFAULT_VALUE,
                ColumnParserInterface::COLUMN_IS_NULLABLE,
                ColumnParserInterface::COLUMN_COLUMN_TYPE,
                ColumnParserInterface::COLUMN_EXTRA
            ])
                ->from(ColumnParserInterface::TABLE_STRUCTURE_INFO)
                ->where([
                    ColumnParserInterface::COLUMN_TABLE_SCHEMA => $this->getTargetDb()
                ])
        )->fetchAll();
    }

    protected function fetchKeyData(): array
    {
        return $this->informationSchema->query(
            Sql::select([
                ColumnKeyParserInterface::COLUMN_KEY_TYPE,
                ColumnKeyParserInterface::COLUMN_KEY_FK_TABLE_SOURCE,
                ColumnKeyParserInterface::COLUMN_KEY_FK_TABLE_TARGET,
                ColumnKeyParserInterface::COLUMN_KEY_FK_COLUMN_SOURCE,
                ColumnKeyParserInterface::COLUMN_KEY_FK_COLUMN_TARGET,
            ])->from(ColumnKeyParserInterface::KEY_TABLE_NAME)->
            where([
                ColumnKeyParserInterface::COLUMN_KEY_FK_SCHEMA => $this->getTargetDb()
            ])
        )->fetchAll();
    }


    protected function parseStructureData(array $dbData): array
    {
        $result = [];
        foreach ($dbData as $dataRow) {
            $columnData = $dataRow[ColumnParserInterface::TABLE_STRUCTURE_INFO];
            $table = $columnData[ColumnParserInterface::COLUMN_TABLE_NAME];
            if (!array_key_exists($table, $result)) {
                $result[$table] = $this->tableFactory->create(['name' => $table]);
            }
            $result[$table]->addColumn($this->columnParser->parse($columnData));
        }
        return $result;
    }

    /**
     * @param array $structure
     * @param array $keyDbData
     * @return array
     */
    protected function addKeyData(array $structure, array $keyDbData): array
    {
        foreach ($keyDbData as $dataRow) {
            $keyData = $dataRow[ColumnKeyParserInterface::KEY_TABLE_NAME];
            $table = $keyData[ColumnKeyParserInterface::COLUMN_KEY_FK_TABLE_SOURCE];
            $column = $structure[$table]->getColumn($keyData[ColumnKeyParserInterface::COLUMN_KEY_FK_COLUMN_SOURCE]);
            $column->addKey($this->columnKeyParser->parse($keyData));
        }
        return $structure;
    }

}