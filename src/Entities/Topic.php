<?php
namespace Fridde;

/**
* @Entity @Table(name="topics")
*/
class Topic
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;
    /** @Column(type="string") */
    protected $Grade;
    /** @Column(type="integer")       */
    protected $VisitOrder;
    /** @Column(type="string") */
    protected $ShortName;
    /** @Column(type="string") */
    protected $LongName;
    /** @ManyToOne(targetEntity="Location", inversedBy="getTopics")     **/
    protected $Location;
    /** @Column(type="string") */
    protected $Food;
    /** @Column(type="string") */
    protected $Url;
    /** @Column(type="integer") */			
    protected $IsLektion;



    public function isLektion()
    {
        return trim($this->pick("IsLektion")) == "true";
    }
}
