<?php

namespace Siarko\DbModelApi\Storage;

interface StorageCredentialProviderInterface
{

    /**
     * @return string database Hostname
     */
    function getHostname(): string;

    /**
     * @return string database name
     */
    function getDatabase(): string;

    /**
     * @return string
     */
    function getUsername(): string;

    /**
     * @return string
     */
    function getPassword(): string;

}