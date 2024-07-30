<?php

namespace Siarko\DbModelApi\Commands;

use Siarko\DbModelApi\Schema\BuilderInterface;
use Siarko\DbModelApi\Schema\Creator\Comparator\SchemaComparatorInterface;
use Siarko\DbModelApi\Schema\Creator\Comparator\SchemaDiffVisualizer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateSchema extends \Symfony\Component\Console\Command\Command
{

    public function __construct(
        protected readonly BuilderInterface $liveDbSchema,
        protected readonly BuilderInterface $fileDbSchema,
        protected readonly SchemaComparatorInterface $schemaComparator,
        protected readonly SchemaDiffVisualizer $diffVisualizer,
    )
    {

        parent::__construct("ValidateSchema");
    }

    protected function configure()
    {
        $this->setName("db:schema:validate")
            ->setDescription("Validate db schema");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileSchema = $this->fileDbSchema->build();
        $liveDbSchema = $this->liveDbSchema->build();
        $fileSchemaChanges = $this->schemaComparator->compare($liveDbSchema, $fileSchema);
        if(count($fileSchemaChanges) > 0){
            $output->writeln("<comment>Differences in schema files that are not present in database</comment>");
            $this->diffVisualizer->listDiff($fileSchemaChanges);
        }else{
            $output->writeln("<info>Db schema files and live database are in sync!</info>");
        }
        return 0;
    }

}