<?php

namespace Fridde\Entities;

use Carbon\Carbon;

/**
 * @Entity(repositoryClass="Fridde\Entities\CookieRepository")
 * @Table(name="cookies")
 * @HasLifecycleCallbacks
 */
class Cookie
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(type="string") */
    protected $Name;

    /** @Column(type="string") */
    protected $Value;

    /** @ManyToOne(targetEntity="School", inversedBy="Hashes")     * */
    protected $School;

    /** @Column(type="integer") */
    protected $Rights;

    /** @Column(type="string", nullable=true) */
    protected $CreatedAt;

    const RIGHTS = [0 => 'no_schools', 1 => 'school_only', 2 => 'all_schools'];

    public const RIGHTS_NO_SCHOOLS = 0;
    public const RIGHTS_SCHOOL_ONLY = 1;
    public const RIGHTS_ALL_SCHOOLS = 2;

    public function getId()
    {
        return $this->id;
    }

    public static function translate($value, $valueType)
    {
        if (is_string($value)) {
            $constant = constant('self::'.strtoupper($valueType));
            $value = array_search($value, $constant);
        }

        return $value;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setName($Name)
    {
        $this->Name = $Name;

        return $this;
    }

    public function getValue()
    {
        return $this->Value;
    }

    public function setValue($Value)
    {
        $this->Value = $Value;

        return $this;
    }

    public function getSchool()
    {
        return $this->School;
    }

    public function getSchoolId()
    {
        return $this->getSchool()->getId();
    }

    public function setSchool(School $School)
    {
        $this->School = $School;
        $School->addPassword($this);

        return $this;
    }

    public function getRights()
    {
        return $this->Rights;
    }

    public function getRightsOptions()
    {
        return self::RIGHTS;
    }

    public function setRights($Rights)
    {
        $this->Rights = self::translate($Rights, 'RIGHTS');

        return $this;
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
        if (!is_string($CreatedAt) && get_class($CreatedAt) == 'Carbon\Carbon') {
            $CreatedAt = $CreatedAt->toIso8601String();
        }
        $this->CreatedAt = $CreatedAt;
    }


    /** @PrePersist */
    public function prePersist()
    {
        $this->setCreatedAt(Carbon::now());
    }

    /** @PreUpdate */
    public function preUpdate()
    {
    }

    /** @PreRemove */
    public function preRemove()
    {
    }

}
