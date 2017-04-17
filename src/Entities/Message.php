<?php
namespace Fridde\Entities;

use Carbon\Carbon;

/**
* @Entity(repositoryClass="Fridde\Entities\MessageRepository")
* @Table(name="messages")
* @HasLifecycleCallbacks
*/
class Message
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @ManyToOne(targetEntity="User", inversedBy="Messages")     **/
    protected $User;

    /** @Column(type="integer", nullable=true) */
    protected $Subject;

    /** @Column(type="integer", nullable=true) */
    protected $Carrier;

    /** @Column(type="integer", nullable=true) */
    protected $Status;

    /** @Column(type="string", nullable=true) */
    protected $ExtId;

    /** @Column(type="text", nullable=true) */
    protected $Content;

    /** @Column(type="string", nullable=true) */
    protected $Timestamp;

    const STATUS_TYPES = [0 => "pending", 1 => "sent", 2 => "received"];
    const CARRIER_TYPES = [0 => "mail", 1 => "sms"];
    const SUBJECT_TYPES = [0 => "confirmation", 1 => "welcome", 2 => "profile_update"];

    public function getId(){return $this->id;}
    public function setId($id){$this->id = $id;}
    public function getUser(){return $this->User;}
    public function setUser($User)
    {
        $this->User = $User;
        $User->addMessage($this);
    }

    public function getSubject()
    {
        return $this->Subject;
    }

    public function getSubjectString()
    {
        return self::SUBJECT_TYPES[$this->Subject] ?? null;
    }

    public function setSubject($Subject)
    {
        if(is_string($Subject)){
            $Subject = array_flip(self::SUBJECT_TYPES)[$Subject];
        }
        $this->Subject = $Subject;
    }

    public function getCarrier()
    {
        return $this->Carrier;
    }

    public function getCarrierString()
    {
        return self::CARRIER_TYPES[$this->Carrier] ?? null;
    }

    public function setCarrier($Carrier)
    {
        if(is_string($Carrier)){
            $Carrier = array_flip(self::CARRIER_TYPES)[$Carrier];
        }
        $this->Carrier = $Carrier;
    }

    public function getStatus()
    {
        return $this->Status;
    }

    public function getStatusString()
    {
        return self::STATUS_TYPES[$this->Status] ?? null;
    }

    public function setStatus($Status)
    {
        if(is_string($Status)){
            $Status = array_flip(self::STATUS_TYPES)[$Status];
        }
        $this->Status = $Status;
    }

    public function getExtId()
    {
        return $this->ExtId;
    }

    public function setExtId($ExtId)
    {
        $this->ExtId = $ExtId;
    }

    public function getContent($key = null)
    {
        $content = json_decode($this->Content, true);
        if(!empty($key)){
            $content = $content[$key] ?? null;
        }
        return $content;
    }

    public function getContentAsString()
    {
        return $this->Content;
    }
    public function setContent(...$args)
    {        

        if(is_array($args[0])){
            $this->Content = json_encode($args[0]);
        } elseif(count($args) === 2){
            $content = $this->getContent();
            $content[$args[0]] = $args[1];
            $this->setContent($content);
        } else {
            $this->Content = $args[0];
        }
    }


    public function getTimestamp()
    {
        if(is_string($this->Timestamp)){
            $this->Timestamp = new Carbon($this->Timestamp);
        }
        return $this->Timestamp;
    }
    public function setTimestamp($Timestamp)
    {
        if(!is_string($Timestamp)){
            $Timestamp = $Timestamp->toIso8601String();
        }
        $this->Timestamp = $Timestamp;
    }

    public function wasSentAfter($date)
    {
        if(is_string($date)){
            $date = new Carbon($date);
        }
        return $this->getTimestamp()->gt($date);
    }

    public function checkProperties($properties, $return = "all_true")
    {
        if(empty($properties)){
            return null;
        }
        $booleans = [];
        foreach($properties as $prop => $val){
            if($prop == "sent_after"){
                $b = $this->wasSentAfter($val);
            } elseif(in_array($prop, ["Status", "Carrier", "Subject"])){
                $method_name = "get" . $prop . "String";
            } else {
                $method_name = "get" . ucfirst($prop);
            }
            $actual_value = $this->$method_name();
            $possible_values = (array) $val;

            $booleans[] = in_array($actual_value, $possible_values);
        }
        $filtered = array_filter($booleans);
        switch($return){
            case "all_true":
            return count($booleans) == count($filtered);
            break;

            case "all_false":
            return empty($filtered);
            break;

            case "count_true":
            return count($filtered);
            break;

            default:
            throw new \Exception("The return type <" . $return . "> is not defined");
            break;
        }
    }


    /** @PrePersist */
    public function prePersist()
    {
        $this->setTimestamp(Carbon::now());
    }
    /** @PreUpdate */
    public function preUpdate()
    {
    }

    /** @PreRemove */
    public function preRemove(){ }

}
