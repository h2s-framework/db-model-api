<?php

namespace Siarko\DbModelApi\Storage;

use PDO;
use Siarko\DbModelApi\Exception\SqlQueryException;
use Siarko\SqlCreator\BasicQuery;


class PDOStorageContext implements StorageContextInterface
{

    private ?\PDO $context = null;
    private ?\PDOException $connectException = null;

    /**
     * @param StorageCredentialProviderInterface $credentialProvider
     * @param StorageQueryResultInterfaceFactory $queryResultFactory
     */
    public function __construct(
        private readonly StorageCredentialProviderInterface $credentialProvider,
        private readonly StorageQueryResultInterfaceFactory $queryResultFactory
    ){
    }

    /**
     * @return string
     */
    function getError(): string
    {
        return $this->connectException?->getMessage() ?? '';
    }

    /**
     * @param string|BasicQuery $sql
     * @param array $bind
     * @return StorageQueryResultInterface
     * @throws SqlQueryException
     */
    function query(string|BasicQuery $sql, array $bind = []): StorageQueryResultInterface
    {
        $this->connect();
        if($sql instanceof BasicQuery){
            $bind = array_merge($sql->getBinds(), $bind);
            $sql = $sql->parse();
        }
        $externalTransaction = $this->context->inTransaction();
        try {
            if(!$externalTransaction){
                $this->beginTransaction();
            }
            $result = $this->executeQuery($sql, $bind);
            if(!$externalTransaction && $this->context->inTransaction()){
                $this->commitTransaction();
            }
        }catch (\Exception $exception){
            if(!$externalTransaction && $this->context->inTransaction()) {
                $this->rollbackTransaction();
            }
            throw new SqlQueryException("SQL: ".$sql, 0, $exception);
        }
        return $this->queryResultFactory->create(['statement' => $result]);
    }

    /**
     * @param string $sql
     * @param array $binds
     * @return \PDOStatement|null
     * @throws \Exception
     */
    protected function executeQuery(string $sql, array $binds = []): ?\PDOStatement{
        if (count($binds) == 0) {
            $result = $this->context->query($sql);
            if (!$result) {
                return null;
            }
        } else {
            $query = $this->context->prepare($sql);
            foreach ($binds as $name => $value) {
                $query->bindValue($name, $value);
            }
            if (!$query->execute()) {
                throw new \Exception($query->errorInfo());
            }
            $result = $query;
        }
        return $result;
    }

    /**
     * @return void
     */
    function connect(): void
    {
        if($this->context !== null){
            return;
        }
        $this->context = new \PDO(
            "mysql:host={$this->credentialProvider->getHostname()};dbname={$this->credentialProvider->getDatabase()}",
            $this->credentialProvider->getUsername(),
            $this->credentialProvider->getPassword()
        );
        $this->context->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->context->setAttribute(PDO::ATTR_AUTOCOMMIT, false);
    }

    /**
     * @return bool
     */
    function beginTransaction(): bool
    {
        $this->connect();
        return $this->context->beginTransaction();
    }

    /**
     * @return bool
     */
    function commitTransaction(): bool
    {
        return $this->context->commit();
    }

    /**
     * @return bool
     */
    function rollbackTransaction(): bool
    {
        return $this->context->rollBack();
    }
}