<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
use Fridde\Timing as T;


/**
 * @ORM\Entity(repositoryClass="Fridde\Entities\UserRepository")
 * @ORM\Table(name="users")
 * @ORM\HasLifecycleCallbacks
 */
class User
{
    /** @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $FirstName;

    /** @ORM\Column(type="string", nullable=true) */
    protected $LastName;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Mobil;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Mail;

    /** @ORM\ManyToOne(targetEntity="School", inversedBy="Users")     * */
    protected $School;

    /** @ORM\Column(type="smallint", nullable=true) */
    protected $Role = self::ROLE_TEACHER;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Acronym;

    /** @ORM\Column(type="smallint", nullable=true) */
    protected $Status = 1;

    /** @ORM\Column(type="string", nullable=true) */
    protected $LastChange;

    /** @ORM\Column(type="string", nullable=true) */
    protected $CreatedAt;

    /** @ORM\Column(type="smallint", nullable=true) */
    protected $MessageSettings;

    /** @ORM\ManyToMany(targetEntity="Visit", mappedBy="Colleagues")
     * @ORM\JoinTable(name="Colleagues_Visits")
     */
    protected $Visits;

    /** @ORM\OneToMany(targetEntity="Message", mappedBy="User")
     * @ORM\OrderBy({"Date" = "ASC"})
     */
    protected $Messages;

    /** @ORM\OneToMany(targetEntity="Group", mappedBy="User") */
    protected $Groups;

    /** @ORM\OneToMany(targetEntity="Note", mappedBy="User") */
    protected $Notes;



    public const ROLE_STAKEHOLDER = 2;
    public const ROLE_TEACHER = 4;
    public const ROLE_SCHOOL_MANAGER = 8;
    public const ROLE_ADMIN = 16;
    public const ROLE_SUPERUSER = 32;

    public const ARCHIVED = 0;
    public const ACTIVE = 1;


    public function __construct()
    {
        $this->Visits = new ArrayCollection();
        $this->Messages = new ArrayCollection();
        $this->Groups = new ArrayCollection();
        $this->Notes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function hasId(int $Id): bool
    {
        return $this->getId() === $Id;
    }

    public function getFirstName()
    {
        return $this->FirstName;
    }

    public function setFirstName($FirstName)
    {
        $this->FirstName = ucfirst(trim($FirstName));
    }

    public function getLastName()
    {
        return $this->LastName;
    }

    public function setLastName($LastName)
    {
        $this->LastName = trim($LastName);
    }

    public function getFullName()
    {
        return $this->FirstName.' '.$this->LastName;
    }

    public function getShortName()
    {
        return $this->FirstName.' '.mb_substr($this->LastName, 0, 1);
    }


    public function getMobil()
    {
        return $this->Mobil;
    }

    public function getStandardizedMobil()
    {
        return $this->standardizeMobNr($this->Mobil);
    }

    public function setMobil($Mobil)
    {
        $mob = preg_replace('/[^0-9+]/', '', $Mobil);
        $this->Mobil = $mob;
    }

    public function hasMobil()
    {
        return !empty(trim($this->Mobil));
    }

    public function getMail()
    {
        return $this->Mail;
    }

    public function setMail($Mail)
    {
        $this->Mail = strtolower(trim($Mail));
    }

    public function hasMail()
    {
        return !empty(trim($this->Mail));
    }

    /**
     * @return \Fridde\Entities\School
     */
    public function getSchool()
    {
        return $this->School;
    }

    public function getSchoolId(): ?string
    {
        $school = $this->getSchool();

        return (empty($school) ? null : $school->getId());
    }

    public function setSchool(School $School)
    {
        $this->School = $School;
        $School->addUser($this);
    }

    public function isFromSchool($school_id)
    {
        $school_id = (array)$school_id;

        return in_array($this->getSchoolId(), $school_id, true);
    }

    public function getRole(): ?int
    {
        return $this->Role;
    }

    public function setRole($Role)
    {
        $this->Role = $Role;
    }

    public function getRoleLabel()
    {
        $labels = self::getRoleLabels();

        return $labels[$this->getRole()] ?? null;
    }

    public static function getRoleLabels()
    {
        return [
            self::ROLE_STAKEHOLDER => 'stakeholder',
            self::ROLE_TEACHER => 'teacher',
            self::ROLE_SCHOOL_MANAGER => 'school_manager',
            self::ROLE_ADMIN => 'admin',
            self::ROLE_SUPERUSER => 'superuser',
        ];
    }

    public function hasRole(int $role)
    {
        return $this->getRole() === $role;
    }

    public function hasOneOfRoles(array $roles)
    {
        return in_array($this->getRole(), $roles, true);

    }


    public function getAcronym(): ?string
    {
        return $this->Acronym;
    }

    public function setAcronym(string $Acronym)
    {
        $this->Acronym = $Acronym;
    }

    public function getStatus(): int
    {
        return $this->Status ?? self::ACTIVE;
    }

    public function getStatusString(): string
    {
        return self::getStatusOptions()[$this->getStatus()];
    }

    public static function getStatusOptions()
    {
        return Group::getStatusOptions();
    }

    public function setStatus(int $Status)
    {
        $this->Status = $Status;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === User::ACTIVE;
        // return $this->getStatusString() === 'active';
    }

    public function getLastChange()
    {
        return $this->LastChange;
    }

    public function setLastChange($LastChange)
    {
        if ($LastChange instanceof Carbon) {
            $LastChange = $LastChange->toIso8601String();
        }
        $this->LastChange = $LastChange;
    }

    public function getCreatedAt()
    {
        if(empty($this->CreatedAt)){
            return null;
        }

        if (is_string($this->CreatedAt)) {
            $this->CreatedAt = new Carbon($this->CreatedAt);
        }

        return $this->CreatedAt;
    }

    public function setCreatedAt($CreatedAt)
    {
        if ($CreatedAt instanceof Carbon) {
            $CreatedAt = $CreatedAt->toIso8601String();
        }
        $this->CreatedAt = $CreatedAt;
    }


    public function getMessageSettings(): int
    {
        return (int) $this->MessageSettings;
    }

    public function setMessageSettings(int $MessageSettings): void
    {
        $this->MessageSettings = $MessageSettings;
    }

    public function hasMessageSetting(int $MessageSetting): bool
    {
        $power = 1 << $MessageSetting;

        return (bool) ($this->getMessageSettings() & $power);
    }

    public function changeMessageSetting(int $MessageSetting, bool $new_status): void
    {
        $old_settings = $this->getMessageSettings();
        $added_setting = 1 << $MessageSetting;

        if((bool)($this->getMessageSettings() & $added_setting) === $new_status){
            return;
        }
        if($new_status){
            $new_settings = $old_settings | $added_setting;
        } else {
            $new_settings = $old_settings ^ $added_setting;
        }

        $this->setMessageSettings($new_settings);
    }

    public function getVisits()
    {
        return $this->Visits;
    }

    public function addVisit($Visit)
    {
        $this->Visits->add($Visit);
    }

    public function removeVisit($Visit)
    {
        $this->Visits->removeElement($Visit);
    }

    public function getMessages(): array
    {
        return $this->Messages->toArray();
    }

    public function getFilteredMessages($properties, array $messages = null): array
    {
        $messages = $messages ?? $this->getMessages();

        return array_filter(
            $messages,
            function (Message $m) use ($properties) {
                return $m->checkProperties($properties);
            }
        );
    }

    public function setMessages($Messages)
    {
        $this->Messages = $Messages;
    }

    public function getGroups(): array
    {
        return $this->Groups->toArray();
    }

    /**
     * @param Group[] $Groups
     */
    public function setGroups(array $Groups): void
    {
        $this->Groups = $Groups;
    }

    public function getActiveGroups(): array
    {
        return array_filter($this->getGroups(), function (Group $g){
            return $g->isActive();
        });
    }

    /**
     * @param int $min
     * @return bool
     */
    public function hasGroups(int $min = 1, bool $active = true, bool $visiting = false)
    {
        $criteria = compact($min, $active, $visiting);

        return $this->hasGroupsWithCriteria($criteria);
    }

    public function hasActiveGroups(): bool
    {
        $criteria = ['active' => true];

        return $this->hasGroupsWithCriteria($criteria);
    }

    public function hasGroupsWithCriteria(array $criteria = []): bool
    {
        $defaults = [
            'min' => 1,
            'active' => true,
            'visiting' => false,
            'in_future' => false,
            'in_segment' => null,
            'next_visit_before' => null,
            'next_visit_not_confirmed' => false
        ];

        $criteria += $defaults;

        $groups = $this->getGroups();
        if (empty($groups) || count($groups) < $criteria['min']) {
            return false;
        }

        $groups = array_filter(
            $groups,
            function (Group $g) use ($criteria) {
                if ($criteria['active'] && !$g->isActive()) {
                    return false;
                }
                if ($criteria['visiting'] && !$g->hasVisits()) {
                    return false;
                }
                if ($criteria['in_future'] && !$g->hasNextVisit()) {
                    return false;
                }
                if(!empty($criteria['in_segment']) && !$g->isSegment($criteria['in_segment'])){
                   return false;
                }
                if(!empty($criteria['next_visit_before']) && $g->hasNextVisit()){
                    if(! $g->getNextVisit()->getDate()->lte($criteria['next_visit_before'])){
                        return false;
                    }
                }
                if(!empty($criteria['next_visit_not_confirmed']) && $g->hasNextVisit()){
                    if($g->getNextVisit()->isConfirmed()){
                        return false;
                    }
                }

                return true;
            }
        );

        return count($groups) >= $criteria['min'];
    }




    public function hasActiveGroupsVisitingInTheFuture(): bool
    {
        $criteria = ['visiting' => true, 'in_future' => true];

        return $this->hasGroupsWithCriteria($criteria);
    }

    public function getGroupIdArray(): array
    {
        return array_map(
            function (Group $g) {
                return $g->getId();
            },
            $this->getGroups()
        );
    }

    public function addGroup(Group $Group): void
    {
        $this->Groups->add($Group);
    }

    public function removeGroup(Group $Group): void
    {
        $this->Groups->removeElement($Group);
    }

    /**
     * @return array
     */
    public function getNotes(): array
    {
        return $this->Notes->toArray();
    }

    /**
     * @param mixed $Notes
     */
    public function setNotes($Notes): void
    {
        $this->Notes = $Notes;
    }

    public function wasCreatedAfter($date): bool
    {
        $created_at = $this->getCreatedAt();
        if(empty($date) || empty($created_at)){
            // we assume the user was created at the beginning of time
            return false;
        }

        if (is_string($date)) {
            $date = new Carbon($date);
        }


        return $date->lte($created_at);
    }

    public function getLastMessage($properties = null): ?Message
    {
        if (!empty($properties)) {
            $msg = $this->getFilteredMessages($properties);
        } else {
            $msg = $this->getMessages();
        }

        return array_pop($msg);
    }


    public function lastMessageWasAfter($date, $properties = null): bool
    {
        $last_message = $this->getLastMessage($properties);
        if (!empty($last_message)) {
            return $last_message->wasSentAfter($date);
        }

        return false;
    }

    /**
     Will also return true if NO message with the given properties ever has been sent

     * @param array|null $time_ago If set to null, the interval is assumed to be [0, 's']
     * @param int|null $subject
     * @return bool
     */
    public function lastMessageWasBefore(array $time_ago = null, int $subject = null): bool
    {
        if(in_array(null, [$time_ago, $subject], true)){
            return true;
        }

        $date = T::subDurationFromNow($time_ago);
        $prop = ['subject' => $subject];

        return ! $this->lastMessageWasAfter($date, $prop);
    }

    public function hasStandardizedMob()
    {
        return $this->standardizeMobNr(null, true);
    }

    public function standardizeMobNr($number = null, $just_check = false)
    {
        $number = empty($number) ? $this->getMobil() : $number;
        $nr = preg_replace('/[\D]/', '', $number);
        $trim_characters = ['0', '4', '6']; // we need to trim from left to right order
        foreach ($trim_characters as $char) {
            $nr = ltrim($nr, $char);
        }
        if (in_array(substr($nr, 0, 2), ['70', '72', '73', '76', '79'], true)) {
            $nr = '+46'.$nr;
            $standardized = true;
        } else {
            $nr = $number;
            $standardized = false;
        }
        if ($just_check) {
            return $standardized;
        }

        return $nr;
    }

    private function setCurrentStandardMailSettings(): void
    {
        $settings = [
            Message::SUBJECT_PASSWORD_RECOVERY,
            Message::SUBJECT_NEW_GROUP,
            Message::SUBJECT_CONTINUED_GROUP,
            Message::SUBJECT_VISIT_CONFIRMATION,
            Message::SUBJECT_INCOMPLETE_PROFILE,
        ];

        foreach($settings as $setting){
            $this->changeMessageSetting($setting, true);
        }
    }

    /** @ORM\PrePersist */
    public function prePersist(): void
    {
        $this->setCreatedAt(Carbon::now());
        $this->setLastChange(Carbon::now()->toIso8601String());
        $this->setCurrentStandardMailSettings();
    }

    /** @ORM\PreUpdate */
    public function preUpdate(): void
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @ORM\PreRemove */
    public function preRemove(): void
    {
    }

    public function getNonNullableFieldNames(string $class_name = self::class)
    {

        $N = $GLOBALS['CONTAINER']->get('Naturskolan');
        $table_name = $N->ORM->EM->getClassMetadata($class_name)->getTableName();
        $columns = $N->ORM->EM->getConnection()->getSchemaManager()->listTableColumns($table_name);

        $return = [];
        foreach ($columns as $col) {
            if ($col->getNotnull()) {
                $return[] = $col->getName();
            }
        }

        return $return;
    }


}
