<?php

namespace Siarko\DbModelApi\Schema\Creator\SchemaFile;

use Butschster\Dbml\Ast\Ref\Type\ManyToOneNode;
use Butschster\Dbml\Ast\Ref\Type\OneToManyNode;
use Butschster\Dbml\DbmlParserFactory;
use JetBrains\PhpStorm\Pure;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Siarko\DbModelApi\Schema\Creator\SchemaFile\AssocParser\Column\DbSchemaFileParser;
use Siarko\Files\Api\FileInterface;
use Siarko\Files\Api\Parse\FileParserInterface;

class DbmlFileParser implements FileParserInterface
{

    /**
     * @param FileInterface $file
     * @return mixed
     */
    function parse(FileInterface $file): mixed
    {
        $dbmlParser = DbmlParserFactory::create();
        try {
            $schema = $dbmlParser->parse($this->fixDbmlContent($file->getContent()));
            return $this->parseSchema($schema);
        } catch (RuntimeExceptionInterface $e) {
            throw new \RuntimeException("Exception while parsing DBML file: ".$file->getPath(), $e->getCode(), $e);
        }
    }

    /**
     * FIXME - Fix for issue with some online generators - they encapsulate table and column names in quotes
     * @param string $content
     * @return string
     */
    protected function fixDbmlContent(string $content): string
    {
        return preg_replace('/"([a-zA-Z_-]+)"\."([a-zA-Z_-]+)"/', '$1.$2', $content);
    }

    /**
     * @param \Butschster\Dbml\Ast\SchemaNode $schema
     * @return array
     */
    private function parseSchema(?\Butschster\Dbml\Ast\SchemaNode $schema): array
    {
        $result = [];
        foreach ($schema->getTables() as $table) {
            $columns = [];
            $keys = [];
            foreach ($table->getColumns() as $column) {
                $columns[$column->getName()] = [
                    DbSchemaFileParser::KEY_TYPE => $this->getTypeString($column->getType()),
                    DbSchemaFileParser::KEY_AUTO_INCREMENT => $column->isIncrement(),
                    DbSchemaFileParser::KEY_NULLABLE => $column->isNull()
                ];
                if($column->getDefault() !== null){
                    $columns[$column->getName()][DbSchemaFileParser::KEY_DEFAULT] = $column->getDefault()->getValue();
                }
                if($column->isPrimaryKey()){
                    $keys[] = [
                        'type' => 'primary',
                        'column' => $column->getName()
                    ];
                }
            }
            $result[$table->getName()] = [
                'columns' => $columns,
                'keys' => $keys
            ];
        }

        foreach ($schema->getRefs() as $ref) {
            if($ref->getType() instanceof ManyToOneNode){
                $source = $ref->getLeftTable();
                $target = $ref->getRightTable();
            }else{
                $source = $ref->getRightTable();
                $target = $ref->getLeftTable();
            }
            $sourceTable = $source->getTable();
            $targetTable = $target->getTable();
            $keys = [];
            $i = 0;
            foreach ($source->getColumns() as $column) {
                $keys[] = [
                    'type' => 'foreign',
                    'column' => $column,
                    'target' => $targetTable.".".$target->getColumns()[$i],
                ];
                $i++;
            }

            $result[$sourceTable]['keys'] = array_merge($result[$sourceTable]['keys'], $keys);
        }

        return $result;
    }

    /**
     * @param \Butschster\Dbml\Ast\Table\Column\TypeNode $type
     * @return string
     */
    private function getTypeString(\Butschster\Dbml\Ast\Table\Column\TypeNode $type): string
    {
        return $type->getName().($type->getSize() !== null ? "(".$type->getSize().")" : '');
    }
}