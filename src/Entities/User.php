<?php
namespace Fridde\Entities;

use \Fridde\{Utility as U};
use \Carbon\Carbon as C;

class User extends Entity
{
    public $messages;
    public $added;
    public $roleMapper = [0 => "teacher", 1 => "rektor", 2 => "administrator",
                        3 => "stakeholder"];

    public function getCompleteName()
    {
        $this->setInformation();
        return $this->pick("FirstName") . " " . $this->pick("LastName");
    }

    private function setMessages()
    {
        if(!isset($this->messages)){
            $messages = $this->getTable("messages");
            $this->messages = U::filterFor($messages, ["User", $this->id], false);
        }
    }

    public function getMessages($type)
    {
        $this->setMessages();
        $messages = $this->messages;
        if(isset($type) && is_string($type)){
            $messages = U::filterFor($messages, ["Type", $type], false);
        }
        return $messages;
    }

    public function getLastestMessage($type = null)
    {
        $messages = $this->getMessages($type);
        $ordered = U::orderBy($messages, "Timestamp", "datestring");
        return array_pop($ordered);
    }

    public function daysSinceLastMessage($type = null)
    {
        $latest_message = $this->getLastestMessage($type) ?? false;
        if($latest_message){
            $latest_message_date = new C($latest_message["Timestamp"]);
            return $latest_message_date->diffInDays($this->_NOW_);
        }
        return false;
    }

    public function hasMessages($type = null)
    {
        return count($this->getMessages($type)) > 0;
    }

    public function setDateAdded()
    {
        $this->setInformation();
        if(empty($this->added)){
            if($this->has("DateAdded")){
                $this->added = new C($this->pick("DateAdded"));
            } else {
                throw new \Exception("The user " . $this->id . " has no value for DateAdded.");
            }
        }
        return $this->added;
    }

    public function getDateAdded()
    {
        $this->setDateAdded();
        return $this->added;
    }

    public function daysSinceAdded()
    {
        $this->setDateAdded();
        return $this->added->diffInDays($this->_NOW_);
    }

    public function getShortName()
    {
        if($this->has("Acronym")){
            return $this->pick("Acronym");
        } else {
            return $this->pick("FirstName") . " " . substr($this->pick("LastName"), 0, 1);
        }
    }

    public function getRoleName()
    {
        $this->setInformation();
        $roleId = $this->pick("Role");
        return $this->roleMapper[$roleId];
    }

}
