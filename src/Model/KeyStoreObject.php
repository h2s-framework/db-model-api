<?php

namespace Siarko\DbModelApi\Model;

class KeyStoreObject
{
    protected array $data = []; //data loaded from db

    protected array $changedData = []; //data set by user

    public function getData(string $name = ''): mixed{
        if(strlen($name) == 0){
            return array_merge_recursive($this->data, $this->changedData);
        }
        return $this->__get($name);
    }

    public function setData(string $name, mixed $value): static{
        $this->__set($name, $value);
        return $this;
    }

    public function setDataArray(array $data){
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }
    }

    public function __set($name, $value)
    {
        $this->changedData[$name] = $value;
    }

    public function __get($name){
        $data = array_merge_recursive($this->data, $this->changedData);
        if(array_key_exists($name, $data)){
            return $data[$name];
        }
        return null;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed|null
     */
    public function __call($name, $arguments)
    {
        $prefix = substr($name, 0, 3);
        if($prefix == 'get'){
            return $this->__get(lcfirst($this->getSnakeCase(substr($name, 3))));
        }
        if($prefix == 'set'){
            $this->__set(lcfirst($this->getSnakeCase(substr($name, 3))), $arguments[0]);
            return $this;
        }
        return null;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getSnakeCase(string $name): string{
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
    }

}