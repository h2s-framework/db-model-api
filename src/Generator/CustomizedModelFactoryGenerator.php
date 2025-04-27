<?php

namespace Siarko\DbModelApi\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Siarko\DbModelApi\AbstractModel;
use Siarko\DbModelApi\Exception\CustomModelGenerationException;
use Siarko\DbModelApi\Schema\StorageInterfaceProxy;
use Siarko\DependencyManager\Generator\IGenerator;
use Siarko\SqlCreator\DatabaseElement\Column;
use Siarko\Utils\Strings;

class CustomizedModelFactoryGenerator implements IGenerator
{

    public const CLASS_SUFFIX = 'AbstractModel';

    /**
     * @param StorageInterfaceProxy $schemaStorageProxy
     */
    public function __construct(
        protected readonly StorageInterfaceProxy $schemaStorageProxy
    )
    {
    }

    /**
     * @param string $className
     * @return bool
     */
    function canGenerate(string $className): bool
    {
        return str_ends_with($className, self::CLASS_SUFFIX);
    }

    /**
     * @param string $fullClassName
     * @return string
     */
    function generate(string $fullClassName): string
    {
        $entityName = Strings::camelCaseToSnakeCase($this->getEntityName($fullClassName));
        return $this->createFileContents($fullClassName, $entityName);
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getEntityName(string $className): string
    {
        $parts = explode('\\', $className);
        $shortClassName = end($parts);
        return substr($shortClassName, 0, strlen($shortClassName)-strlen(self::CLASS_SUFFIX));
    }

    /**
     * @param string $fullClassName
     * @param string $entityName
     * @return string
     * @throws CustomModelGenerationException
     */
    protected function createFileContents(string $fullClassName, string $entityName): string
    {
        $schema = $this->schemaStorageProxy->get()->getEntity($entityName);
        if($schema == null){
            throw new CustomModelGenerationException("Entity table schema for $entityName does not exist");
        }
        $file = new PhpFile();
        $class = $this->createClass($file, $fullClassName);

        $this->createTableNameGetterMethod($class, $entityName);

        foreach ($schema->getColumns() as $column) {
            $class->addConstant(strtoupper($column->getName()), $column->getName())->setPublic();

            $this->createColumnGetter($class, $column);
            $this->createColumnSetter($class, $column);
        }

        return $file;
    }

    /**
     * @param PhpFile $file
     * @param string $className
     * @return ClassType
     */
    private function createClass(PhpFile $file, string $className): ClassType {
        $class = $file->setStrictTypes()->addClass($className);
        $class->setExtends(AbstractModel::class);
        $class->addComment('This class was generated automatically by H2S Framework.');
        return $class;
    }

    /**
     * @param ClassType $class
     * @param string $tableName
     * @return void
     */
    private function createTableNameGetterMethod(ClassType $class, string $tableName): void {
        $getEntityName = $class->addMethod('getEntityName');
        $getEntityName->setPublic();
        $getEntityName->setStatic();
        $getEntityName->setReturnType('string');
        $getEntityName->setBody("return '$tableName';");
        $getEntityName->addComment('@description Returns the name of the table in the database.');
        $getEntityName->addComment('@return string');
    }

    /**
     * @param ClassType $class
     * @param Column $column
     * @return void
     */
    private function createColumnGetter(ClassType $class, Column $column): void {
        $type = $column->getType()->getPhpType();
        $name = ucfirst(Strings::snakeCaseToCamelCase($column->getName()));
        $method = $class->addMethod("get$name");
        $method->setPublic();
        $method->setReturnType($type);
        $method->setBody("return \$this->__get(static::".strtoupper($column->getName()).");");
        $method->addComment('@description Returns '.$column->getName());
        $method->addComment('@return '.$type);
    }

    /**
     * @param ClassType $class
     * @param Column $column
     * @return void
     */
    private function createColumnSetter(ClassType $class, Column $column): void {
        $type = $column->getType()->getPhpType();
        $name = ucfirst(Strings::snakeCaseToCamelCase($column->getName()));
        $method = $class->addMethod("set$name");
        $method->setPublic();
        $method->setReturnType('self');
        $method->setBody('$this->__set(static::'.strtoupper($column->getName()).', $value); return $this;');
        $method->addParameter('value')->setType($type);
        $method->addComment('@description Sets '.$column->getName());
        $method->addComment('@param '.$type.' $value');
        $method->addComment('@return self');
    }
}