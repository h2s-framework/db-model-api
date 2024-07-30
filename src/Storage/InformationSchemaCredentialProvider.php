<?php

namespace Siarko\DbModelApi\Storage;

class InformationSchemaCredentialProvider implements StorageCredentialProviderInterface
{

    public function __construct(
        private readonly StorageCredentialProviderInterface $baseStorageProvider,
        private readonly string $informationSchemaDatabase = 'INFORMATION_SCHEMA'
    )
    {
    }

    /**
     * @inheritDoc
     */
    function getHostname(): string
    {
        return $this->baseStorageProvider->getHostname();
    }

    /**
     * @inheritDoc
     */
    function getDatabase(): string
    {
        return $this->informationSchemaDatabase;
    }

    /**
     * @inheritDoc
     */
    function getUsername(): string
    {
        return $this->baseStorageProvider->getUsername();
    }

    /**
     * @inheritDoc
     */
    function getPassword(): string
    {
        return $this->baseStorageProvider->getPassword();
    }
}