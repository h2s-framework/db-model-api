<?php

namespace Siarko\DbModelApi\Generator;

use Nette\PhpGenerator\PhpFile;
use Siarko\DbModelApi\AbstractModel;
use Siarko\DbModelApi\Exception\CustomModelGenerationException;
use Siarko\DbModelApi\Schema\StorageInterfaceProxy;
use Siarko\DependencyManager\Generator\IGenerator;
use Siarko\Utils\Strings;

class CustomizedModelFactoryGenerator implements IGenerator
{

    public const CLASS_SUFFIX = 'CustomAbstractModel';

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
        $file->setStrictTypes();
        $class = $file->addClass($fullClassName);
        $class->setExtends(AbstractModel::class);

        $getEntityName = $class->addMethod('getEntityName');
        $getEntityName->setPublic();
        $getEntityName->setStatic(true);
        $getEntityName->setReturnType('string');
        $getEntityName->setBody("return '$entityName';");

        foreach ($schema->getColumns() as $column) {
            $type = $column->getType()->getPhpType();
            $camelCaseName = Strings::snakeCaseToCamelCase($column->getName());
            $name = ucfirst($camelCaseName);
            $class->addComment("@method $type get$name()");
            $class->addComment("@method void set$name($type \$$camelCaseName)");

            $class->addConstant(strtoupper($column->getName()), $column->getName())->setPublic();
        }

        return $file;
    }
}