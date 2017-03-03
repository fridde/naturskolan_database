<?php
namespace Fridde\Entities;

/**
* @Entity(repositoryClass="Fridde\Entities\CookieRepository")
* @Table(name="cookies")
*/
class Cookie
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="string") */
    protected $Name;

    /** @Column(type="string") */
    protected $Value;

    /** @ManyToOne(targetEntity="School", inversedBy="Hashes")     **/
    protected $School;

    /** @Column(type="integer") */
    protected $Rights;

    const RIGHTS = [0 => "no_schools", 1 => "school_only", 2 => "all_schools"];

    public function getId(){return $this->id;}

    public static function translate($value, $valueType)
    {
        if(is_string($value)){
            $constant = constant("self::" . strtoupper($valueType));
            $value = array_search($value, $constant);
        }
        return $value;
    }

    public function getName(){return $this->Name;}
    public function setName($Name)
    {
        $this->Name = $Name;
        return $this;
    }

    public function getValue(){return $this->Value;}
    public function setValue($Value)
    {
        $this->Value = $Value;
        return $this;
    }
    public function getSchool(){return $this->School;}

    public function getSchoolId()
    {
        return $this->getSchool()->getId();
    }

    public function setSchool($School){
        $this->School = $School;
        $School->addPassword($this);
        return $this;
    }
    public function getRights(){return $this->Rights;}
    public function getRightsOptions()
    {
        return self::RIGHTS;
    }

    public function setRights($Rights)
    {
        $this->Rights = self::translate($Rights, "RIGHTS");
        return $this;
    }

    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

}
