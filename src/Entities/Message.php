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
    protected User $User;

    /** @ORM\Column(type="smallint") */
    protected int $Subject;

    /** @ORM\Column(type="smallint") */
    protected int $Carrier;

    /** @ORM\Column(type="string") */
    protected $Date;

    public const CARRIER_MAIL = 0;
    public const CARRIER_SMS = 1;

    public const SUBJECT_PASSWORD_RECOVERY = 1;
    public const SUBJECT_VISIT_CONFIRMATION = 2 ;
    public const SUBJECT_INCOMPLETE_PROFILE = 3;
    public const SUBJECT_NEW_GROUP = 4;
    public const SUBJECT_CONTINUED_GROUP = 5;
    public const SUBJECT_ADMIN_SUMMARY = 6;
    public const SUBJECT_USER_REMOVAL_REQUEST = 7;

    public const SUBJECT_LABELS = [
            self::SUBJECT_VISIT_CONFIRMATION => 'Bekräfta ditt besök!',
            self::SUBJECT_INCOMPLETE_PROFILE => 'Vi behöver mer information från dig!',
            self::SUBJECT_NEW_GROUP => 'Året med Naturskolan börjar!',
            self::SUBJECT_CONTINUED_GROUP => 'Snart fortsätter året med Naturskolan',
    ];

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }


    public function getUser(): User
    {
        return $this->User;
    }

    public function setUser(User $User): void
    {
        $this->User = $User;
    }

    public function getSubject(): ?int
    {
        return $this->Subject;
    }

    public function setSubject(int $Subject): void
    {
        $this->Subject = $Subject;
    }

    public function getCarrier(): ?int
    {
        return $this->Carrier;
    }

    public function setCarrier(int $Carrier): void
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

    public function setDate(string $Date): void
    {
        if ($Date instanceof Carbon) {
            $Date = $Date->toDateString();
        }
        $this->Date = $Date;
    }

    public function wasSentAfter($date): bool
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
    public function prePersist(): void
    {
    }

    /** @ORM\PreUpdate */
    public function preUpdate(): void
    {
    }

    /** @ORM\PreRemove */
    public function preRemove(): void
    {
    }

}
