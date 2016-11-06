<?php
namespace Fridde;

class Entity extends Naturskolan
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

}
