<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping AS ORM;
use Fridde\Error\Error;
use Fridde\Error\NException;

/**
 * @ORM\Entity(repositoryClass="Fridde\Entities\MessageRepository")
 * @ORM\Table(name="messages")
 * @ORM\HasLifecycleCallbacks
 */
class Message
{
    /** @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\ManyToOne(targetEntity="User", inversedBy="Messages")     * */
    protected $User;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Subject;

    /** @ORM\Column(type="smallint", nullable=true) */
    protected $Carrier;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Date;

    public const CARRIER_MAIL = 0;
    public const CARRIER_SMS = 1;

    public const SUBJECT_PASSWORD_RECOVERY = 1;
    public const SUBJECT_WELCOME_NEW_USER = 2;
    public const SUBJECT_VISIT_CONFIRMATION = 3 ;
    public const SUBJECT_PROFILE_UPDATE = 4;
    public const SUBJECT_CHANGED_GROUPS = 5;
    public const SUBJECT_MANAGER_MOBILIZATION = 6;
    public const SUBJECT_ADMIN_SUMMARY = 7;
    public const SUBJECT_UPDATE_RECEIVED_SMS = 8;
    public const SUBJECT_USER_REMOVAL_REQUEST = 9;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return User
     */
    public function getUser()
    {
        return $this->User;
    }

    public function setUser(User $User)
    {
        $this->User = $User;
    }

    public function getSubject(): ?int
    {
        return $this->Subject;
    }

    public function setSubject(int $Subject)
    {
        $this->Subject = $Subject;
    }

    public function getCarrier(): ?int
    {
        return $this->Carrier;
    }

    public function setCarrier($Carrier)
    {
        $this->Carrier = $Carrier;
    }

    public function getDate(): Carbon
    {
        if (is_string($this->Date)) {
            $this->Date = new Carbon($this->Date);
        }

        return $this->Date;
    }

    public function setDate($Date)
    {
        if ($Date instanceof Carbon) {
            $Date = $Date->toDateString();
        }
        $this->Date = $Date;
    }

    public function wasSentAfter($date)
    {
        if (is_string($date)) {
            $date = new Carbon($date);
        }

        return $this->getDate()->gt($date);
    }

    public function checkProperties(array $properties = [], string $return = 'all_true')
    {
        if (empty($properties)) {
            return null;
        }
        $booleans = [];
        foreach ($properties as $prop => $val) {
            if ($prop === 'sent_after') {
                $booleans[] = $this->wasSentAfter($val);
                continue;
            }
            $method_name = 'get'.ucfirst($prop);

            $actual_value = $this->$method_name();
            $possible_values = (array)$val;

            $booleans[] = in_array($actual_value, $possible_values, true);
        }
        $filtered = array_filter($booleans);
        switch ($return) {
            case 'all_true':
                return count($booleans) === count($filtered);
                break;

            case 'all_false':
                return empty($filtered);
                break;

            case 'count_true':
                return count($filtered);
                break;

            default:
                throw new NException(Error::INVALID_OPTION, [$return]);
        }
    }


    /** @ORM\PrePersist */
    public function prePersist()
    {
    }

    /** @ORM\PreUpdate */
    public function preUpdate()
    {
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
    }

}
