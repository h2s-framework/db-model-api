<?php

namespace Siarko\DbModelApi\Commands;

use Siarko\DbModelApi\Schema\BuilderInterface;
use Siarko\DbModelApi\Schema\Creator\Comparator\SchemaComparatorInterface;
use Siarko\DbModelApi\Schema\Creator\Comparator\SchemaDiffVisualizer;
use Siarko\DbModelApi\Schema\Creator\SchemaWriter;
use Siarko\DbModelApi\Schema\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateSchema extends \Symfony\Component\Console\Command\Command
{

    public function __construct(
        protected readonly BuilderInterface $fileDbSchema,
        protected readonly BuilderInterface $liveDbSchema,
        protected readonly StorageInterface $schemaStorage,
        protected readonly SchemaWriter $schemaWriter,
        protected readonly SchemaComparatorInterface $schemaComparator,
        protected readonly SchemaDiffVisualizer $diffVisualizer
    )
    {
        parent::__construct("Update Db Schema");
    }

    protected function configure()
    {
        $this->setName("db:schema:update")
            ->setDescription("Apply db schema updates to database instance")
            ->addOption(
                'destructive',
                'd',
                InputOption::VALUE_NONE,
                "In destructive mode, tables/columns/constraints are removed"
            )
            ->addOption(
                'no-interaction',
                'n',
                InputOption::VALUE_NONE,
                "No interaction mode, updates will be applied without questions"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $noInteraction = $input->getOption('no-interaction');
        $helper = $this->getHelper('question');
        if($input->getOption('destructive')){
            $this->schemaWriter->setDestructive(true);
            $output->writeln("<info>Running in destructive mode - entities can be removed</info>");
        }
        $fileSchema = $this->fileDbSchema->build();
        $liveSchema = $this->liveDbSchema->build();
        $diff = $this->schemaComparator->compare($liveSchema, $fileSchema);
        if(count($diff) == 0){
            $output->writeln("<info>No changes detected</info>");
            return Command::SUCCESS;
        }
        $output->writeln("<comment>== Detected changes ==</comment>");
        $this->diffVisualizer->listDiff($diff);

        $answer = true;
        if(!$noInteraction){
            $question = new ConfirmationQuestion("Do you want to apply changes to live DB [y/N]?", false);
            $answer = $helper->ask($input, $output, $question);
        }
        if($answer || $noInteraction){
            $this->schemaStorage->setSchema($fileSchema);
            $output->writeln("<info>Updates Applied</info>");
        }

        return 0;
    }

}