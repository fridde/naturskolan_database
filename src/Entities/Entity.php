<?php
namespace Fridde\Entities;

use \Fridde\{Utility as U, Naturskolan};

abstract class Entity extends \Fridde\Naturskolan
{
    public $N;
    public $information;
    public $id;
    public $corresponding_table;
    public $updates = [];

    function __construct ($information = null)
    {
        $this->N = new Naturskolan;
        if(is_array($information)){
            $this->information = $information;
            $this->id = $information["id"];
        }
        else {
            $this->id = $information;
        }
        $class_array = explode('\\', get_called_class());
        $class_name = array_pop($class_array);
        $this->corresponding_table = strtolower($class_name) . "s";
    }

    /**
     * Gets the corresponding value given by the column name
     * @param  string $key A key available in the the information-array corresponding
     * to a column in the table of the entity
     * @return string    The value
     */
    public function pick($key)
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

        $full_class_name = __NAMESPACE__ . "\\" . $key;
        if(class_exists($full_class_name)){
            $foreign_id = $this->pick($key);
            return new $full_class_name($foreign_id);
        } else {
            throw new \Exception("The key $full_class_name can't be converted to an object since the class is not defined");
        }

    }

    public function has($index)
    {
        $this->setInformation();
        return trim($this->information[$index]) != "";
    }

    public function setInformation()
    {
        if(!isset($this->information)){
            $this->information = U::getById($this->getTable($this->corresponding_table), $this->id);
        }
    }

    private function getAll()
    {
        return $this->getTable($this->corresponding_table);
    }

    public function set($key, $value = null)
    {
        $this->information[$key] = $value;
        $this->updates[$key] = $value;
    }

    public function getUpdateArray()
    {
        return [[$this->updates],["id", $this->id]];
    }

}
