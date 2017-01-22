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

    const COOKIE_HASH = 0;
    const PASSWORD = 1;

    const NO_SCHOOLS = 0;
    const SCHOOL_ONLY = 1;
    const ALL_SCHOOLS = 2;

    public function getId(){return $this->id;}
    public function getType(){return $this->Type;}
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
        return $this->Type === self::HASH;
    }
    public function isPassword()
    {
        return $this->Type === self::PASSWORD;
    }
    public function getValue(){return $this->Value;}
    public function setValue($Value)
    {
        $this->Value = $Value;
        return $this;
    }
    public function getSchool(){return $this->School;}
    public function setSchool($School){
        $this->School = $School;
        $School->addPassword($this);
        return $this;
    }
    public function getRights(){return $this->Rights;}
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
