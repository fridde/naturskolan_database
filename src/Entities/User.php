<?php
namespace Fridde\Entities;

class User extends Entity
{
    public $messages;
    public $added;

    public function getCompleteName()
    {
        $this->setInformation();
        return $this->get("FirstName") . " " . $this->get("LastName");
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
            $messages = U::filterFor($messages, ["Type", $type]);
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
            return $latest_message_date->diffInDays($this->now);
        }
        return false;
    }

    public function hasMessages($type = null)
    {
        return count($this->getMessages($type)) > 0;
    }

    public function getDateAdded()
    {
        $this->setInformation();
        $this->added = $this->added ?? new C($this->get("DateAdded"));
        return $this->added;
    }

    public function daysSinceAdded()
    {
        return $this->added->diffInDays($this->now);
    }

}
