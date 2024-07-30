<?php

namespace Siarko\DbModelApi\Schema\Creator\Comparator;

use Siarko\SqlCreator\DatabaseElement\Creator\Constraint;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaDiffVisualizer
{

    public function __construct(
        protected OutputInterface $output
    )
    {
    }

    /**
     * @param array $diff
     * @param OutputInterface|null $output
     */
    public function listDiff(array $diff, OutputInterface $output = null){
        $this->output ??= $output;
        $this->displayTableChanges($diff);
    }

    /**
     * @param array $changes
     */
    protected function displayTableChanges(array $changes)
    {
        foreach ($changes as $changeType => $changeData) {
            if($changeType == SchemaComparatorInterface::KEY_DROP || $changeType == SchemaComparatorInterface::KEY_CREATE){
                $i = 1;
                $this->output->writeln($changeType." Tables:");
                foreach ($changeData as $tableName => $data) {
                    $this->output->writeln($i++.'. '.$tableName);
                }
            }
            if($changeType == SchemaComparatorInterface::KEY_MODIFY){
                $i = 1;
                $this->output->writeln("MODIFY Tables:");
                foreach ($changeData as $tableName => $data) {
                    $this->output->writeln($i++.'. '.$tableName);
                    $this->displayColumnChanges($data);
                }
            }
        }
    }

    /**
     * @param array $columnChanges
     */
    protected function displayColumnChanges(array $columnChanges){
        $prefix = "\t";
        $columnCreator = new \Siarko\SqlCreator\DatabaseElement\Creator\Column();
        foreach ($columnChanges as $changeType => $changeData) {
            if($changeType == SchemaComparatorInterface::KEY_DROP || $changeType == SchemaComparatorInterface::KEY_CREATE){
                $i = 1;
                $this->output->writeln($prefix.$changeType." Columns");
                foreach ($changeData as $columnName => $data) {
                    $this->output->writeln($prefix.$i++.'. '.$columnCreator->createSql($data));
                }
            }
            if($changeType == SchemaComparatorInterface::KEY_MODIFY){
                $i = 1;
                $this->output->writeln($prefix."MODIFY Columns:");
                foreach ($changeData as $columnName => $data) {
                    $this->output->writeln($prefix.$i++.'. '.$columnName);
                    $j = 1;
                    foreach ($data as $propertyName => $propertyData) {
                        if($propertyName == 'keys'){
                            $this->output->writeln($prefix.$prefix.$j++.". Key changes:");
                            $this->displayKeyChanges($propertyData);
                        }else{
                            $this->output->writeln($prefix.$prefix.$j++.'. '.$propertyName.' => \''.$propertyData."'");
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $keyChanges
     */
    protected function displayKeyChanges(array $keyChanges){
        $prefix = "\t\t\t";
        $keyCreator = new Constraint();
        foreach ($keyChanges as $changeType => $changeData) {
            $i = 1;
            $this->output->writeln($prefix.$changeType." Keys");
            foreach ($changeData as $keyName => $keyData) {
                $this->output->writeln($prefix."\t".$i++.". ".$keyCreator->createSql($keyData));
            }
        }
    }

}