<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @Entity(repositoryClass="Fridde\Entities\UserRepository")
 * @Table(name="users")
 * @HasLifecycleCallbacks
 */
class User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string", nullable=true) */
    protected $FirstName;

    /** @Column(type="string", nullable=true) */
    protected $LastName;

    /** @Column(type="string", nullable=true) */
    protected $Mobil;

    /** @Column(type="string", nullable=true) */
    protected $Mail;

    /** @ManyToOne(targetEntity="School", inversedBy="Users")     * */
    protected $School;

    /** @Column(type="smallint", nullable=true) */
    protected $Role = self::ROLE_TEACHER;

    /** @Column(type="string", nullable=true) */
    protected $Acronym;

    /** @Column(type="smallint", nullable=true) */
    protected $Status = 1;

    /** @Column(type="string", nullable=true) */
    protected $LastChange;

    /** @Column(type="string", nullable=true) */
    protected $CreatedAt;

    /** @Column(type="smallint", nullable=true) */
    protected $MessageSettings; 

    /** @ManyToMany(targetEntity="Visit", mappedBy="Colleagues")
     * @JoinTable(name="Colleagues_Visits")
     */
    protected $Visits;

    /** @OneToMany(targetEntity="Message", mappedBy="User") */
    protected $Messages;

    /** @OneToMany(targetEntity="Group", mappedBy="User") */
    protected $Groups;

    /** @OneToMany(targetEntity="Note", mappedBy="User") */
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
        return $this->getStatusString() === 'active';
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

    public function getFilteredMessages($properties, array $messages = null)
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
     * @param int $min
     * @return bool
     */
    public function hasGroups(int $min = 1, bool $active = true, bool $visiting = false)
    {
        $groups = $this->getGroups();

        if($active || $visiting){
            $groups = array_filter(
                $groups,
                function (Group $g) use ($active, $visiting){
                    if($active && ! $g->isActive()){
                        return false;
                    }
                    if($visiting && ! $g->hasVisits()){
                        return false;
                    }
                    return true;
                }
            );
        }

        return count($groups) >= $min;
    }

    public function hasActiveGroups()
    {
        return $this->hasGroups();
    }

    public function getGroupIdArray()
    {
        return array_map(
            function (Group $g) {
                return $g->getId();
            },
            $this->getGroups()
        );
    }

    public function addGroup($Group)
    {
        $this->Groups->add($Group);
    }

    public function removeGroup($Group)
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

    public function wasCreatedAfter($date)
    {
        if (is_string($date)) {
            $date = new Carbon($date);
        }
        $created_at = $this->getCreatedAt() ?? Carbon::now();

        return $date->lte($created_at);
    }

    public function sortMessagesByDate()
    {
        $messages = $this->getMessages();

        uasort(
            $messages,
            function (Message $a, Message $b) {
                $a_ts = $a->getTimestamp();
                $b_ts = $b->getTimestamp();
                if (empty($a_ts) && empty($b_ts)) {
                    return 0;
                } elseif (empty($a_ts)) {
                    return 1;
                } elseif (empty($b_ts)) {
                    return -1;
                } else {
                    return $a_ts->lt($b_ts) ? -1 : 1;
                }

            }
        );
        $this->Messages = new ArrayCollection($messages);

        return $this->Messages;
    }


    /**
     * [getLastMessage description]
     * @param  string $type [description]
     * @param  string $carrier [description]
     * @return [type]          [description]
     */
    public function getLastMessage($properties = null)
    {
        $this->sortMessagesByDate();
        if (!empty($properties)) {
            $msg = $this->getFilteredMessages($properties);
        } else {
            $msg = $this->getMessages();
        }

        return array_pop($msg);
    }


    public function lastMessageWasAfter($date, $properties = null)
    {
        $last_message = $this->getLastMessage($properties);
        if (!empty($last_message)) {
            return $last_message->wasSentAfter($date);
        }

        return false;
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

    private function setCurrentStandardMailSettings()
    {
        $settings = [
            Message::SUBJECT_PASSWORD_RECOVERY,
            Message::SUBJECT_WELCOME_NEW_USER,
            Message::SUBJECT_VISIT_CONFIRMATION,
            Message::SUBJECT_PROFILE_UPDATE,
            Message::SUBJECT_CHANGED_GROUPS,
        ];

        foreach($settings as $setting){
            $this->changeMessageSetting($setting, true);
        }
    }

    /** @PrePersist */
    public function prePersist()
    {
        $this->setCreatedAt(Carbon::now());
        $this->setLastChange(Carbon::now()->toIso8601String());
        $this->setCurrentStandardMailSettings();
    }

    /** @PreUpdate */
    public function preUpdate()
    {
        $this->setLastChange(Carbon::now()->toIso8601String());
    }

    /** @PreRemove */
    public function preRemove()
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
