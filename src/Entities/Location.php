<?php
namespace Fridde;

/**
* @Entity @Table(name="locations")
*/
class Location
{
    /** @Id @Column(type="integer") @GeneratedValue    */
    protected $id;
    /** @Column(type="string") */
    protected $Name;
    /** @Column(type="string") */	 	
    protected $Coordinates;

}
