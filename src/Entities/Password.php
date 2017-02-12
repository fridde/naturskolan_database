<?php
namespace Fridde\Entities;

/**
* @Entity(repositoryClass="Fridde\Entities\PasswordRepository")
* @Table(name="passwords")
*/
class Password
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;

    /** @Column(type="integer") */
    protected $Type;

    /** @Column(type="string") */
    protected $Value;

    /** @ManyToOne(targetEntity="School", inversedBy="Passwords")     **/
    protected $School;

    /** @Column(type="integer") */
    protected $Rights;

    const TYPES = [0 => "cookie_hash", 1 => "password"];
    const RIGHTS = [0 => "no_schools", 1 => "school_only", 2 => "all_schools"];

    public function getId(){return $this->id;}
    public function getType(){return $this->Type;}

    public function getTypeOptions()
    {
        return self::TYPES;
    }

    public static function resolveTypeAsIndex($string)
    {
        return array_search($string, self::TYPES);
    }

    public function setType($Type){
        if(is_string($Type)){
            $const_name = strtoupper($Type);
            $Type = self::$const_name;
        }
        $this->Type = $Type;
        return $this;
    }
    public function isHash()
    {
        return self::TYPES[$this->Type] === "cookie_hash";
    }
    public function isPassword()
    {
        return self::TYPES[$this->Type] === "password";
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
        $this->Rights = $Rights;
        return $this;
    }

    /** @PostPersist */
    public function postPersist(){ }
    /** @PostUpdate */
    public function postUpdate(){ }
    /** @PreRemove */
    public function preRemove(){ }

}
