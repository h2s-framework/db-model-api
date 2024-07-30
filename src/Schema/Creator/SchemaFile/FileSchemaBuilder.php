<?php

namespace Siarko\DbModelApi\Schema\Creator\SchemaFile;

use Siarko\ConfigFiles\Api\Provider\ConfigProviderInterface;
use Siarko\SqlCreator\DatabaseElement\AssocParser\Column\ColumnParserInterface;
use Siarko\SqlCreator\DatabaseElement\AssocParser\Column\Key\ColumnKeyParserInterface;
use Siarko\SqlCreator\DatabaseElement\TableFactory;

class FileSchemaBuilder implements \Siarko\DbModelApi\Schema\BuilderInterface
{

    public const FILE_NAME = 'db_schema';

    /**
     * @param ConfigProviderInterface $configProvider
     * @param TableFactory $tableFactory
     * @param ColumnParserInterface $columnParser
     * @param ColumnKeyParserInterface $columnKeyParser
     * @param string $fileName
     */
    public function __construct(
        protected readonly ConfigProviderInterface  $configProvider,
        protected readonly TableFactory             $tableFactory,
        protected readonly ColumnParserInterface    $columnParser,
        protected readonly ColumnKeyParserInterface $columnKeyParser,
        protected readonly string                   $fileName = 'db_schema',
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function build(): array
    {
        $structureDescription = $this->configProvider->fetch($this->fileName);
        return $this->processStructure($structureDescription);
    }

    /**
     * @param array $structureDescription
     * @return array
     */
    protected function processStructure(array $structureDescription): array
    {
        $result = [];
        foreach ($structureDescription as $tableName => $tableData) {
            $table = $this->tableFactory->create(['name' => $tableName]);
            if (array_key_exists('columns', $tableData)) {
                foreach ($tableData['columns'] as $columnName => $columnData) {
                    $columnData['name'] = $columnName;
                    $columnData['tableName'] = $tableName;
                    $table->addColumn($this->columnParser->parse($columnData));
                }
                $result[$tableName] = $table;
            }
            if (array_key_exists('keys', $tableData)) {
                foreach ($tableData['keys'] as $keyData) {
                    $keyData['sourceTable'] = $tableName;
                    $column = $table->getColumn($keyData['column']);
                    $column->addKey($this->columnKeyParser->parse($keyData));
                }
            }
        }
        return $result;
    }
}