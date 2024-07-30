<?php

namespace Siarko\DbModelApi\Storage\StorageCredentialProvider;

use Siarko\BootConfig\BootConfig;
use Siarko\Utils\DynamicDataObject;

class KeystoreCredentialProvider extends DynamicDataObject implements \Siarko\DbModelApi\Storage\StorageCredentialProviderInterface
{

    private const CONFIG_SECTION = 'db';
    private const FIELD_HOSTNAME = 'host';
    private const FIELD_DATABASE = 'name';
    private const FIELD_USERNAME = 'user';
    private const FIELD_PASSWORD = 'password';

    public function __construct(
        BootConfig $bootConfig
    ){
        parent::__construct($bootConfig->getData(self::CONFIG_SECTION));
    }

    /**
     * @inheritDoc
     */
    function getHostname(): string
    {
        return $this->getData(self::FIELD_HOSTNAME);
    }

    /**
     * @inheritDoc
     */
    function getDatabase(): string
    {
        return $this->getData(self::FIELD_DATABASE);
    }

    /**
     * @inheritDoc
     */
    function getUsername(): string
    {
        return $this->getData(self::FIELD_USERNAME);
    }

    /**
     * @inheritDoc
     */
    function getPassword(): string
    {
        return $this->getData(self::FIELD_PASSWORD);
    }
}