<?php
namespace Fridde\Entities;

class Entity extends Fridde\Naturskolan
{
    public $information;
    public $id;
    public $corresponding_table;

    function __construct ($information = null)
    {
        if(is_array($information)){
            $this->information = $information;
            $this->id = $information["id"];
        }
        else {
            $this->id = $information;
        }
        $this->corresponding_table = strtolower(get_class($this));
    }

    public function get($key)
    {
        $this->setInformation();
        return $this->information[$key] ?? false;
    }

    public function getInfo()
    {
        $this->setInformation();
        return $this->information;
    }

    public function getAsObject($key){
        if(class_exists($key)){
            $foreign_id = $this->get($key);
            return new $key($foreign_id);
        } else {
            throw new Exception("The key $key can't be converted to an object since the class is not defined");
        }

    }

    public function has($index)
    {
        $this->setInformation();
        return trim($this->information[$index]) != "";
    }

    private function setInformation()
    {
        if(!isset($this->information)){
            $this->information = U::getById($this->getTable($this->corresponding_table), $this->id);
        }
    }

    private function getAll()
    {
        return $this->getTable($this->corresponding_table);
    }

}
