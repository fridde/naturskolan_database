<?php

namespace Fridde\Entities;

/**
 * @Entity(repositoryClass="Fridde\Entities\GroupCountRepository")
 * @Table(name="group_counts")
 */
class GroupCount
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @ManyToOne(targetEntity="School", inversedBy="GroupCounts")     * */
    protected $School;

    /** @Column(type="smallint") */
    protected $StartYear;

    /** @Column(type="string") */
    protected $Segment;

    /** @Column(type="smallint") */
    protected $Number;

    /**
     * GroupCount constructor.
     * @param $School
     * @param $StartYear
     * @param $Segment
     * @param $Number
     */
    public function __construct(School $School = null, int $StartYear = null, string $Segment = null, int $Number = 0)
    {
        $this->School = $School;
        $this->StartYear = $StartYear;
        $this->Segment = $Segment;
        $this->Number = $Number;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return School
     */
    public function getSchool(): School
    {
        return $this->School;
    }

    /**
     * @param mixed $School
     */
    public function setSchool(School $School): void
    {
        $this->School = $School;
    }

    /**
     * @return mixed
     */
    public function getStartYear()
    {
        return $this->StartYear;
    }

    /**
     * @param mixed $StartYear
     */
    public function setStartYear($StartYear): void
    {
        $this->StartYear = $StartYear;
    }

    /**
     * @return mixed
     */
    public function getSegment()
    {
        return $this->Segment;
    }

    /**
     * @param mixed $Segment
     */
    public function setSegment($Segment): void
    {
        $this->Segment = $Segment;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->Number;
    }

    /**
     * @param mixed $Number
     */
    public function setNumber($Number): void
    {
        $this->Number = $Number;
    }

    public function matches(int $start_year, string $segment): bool
    {
        if ($this->getStartYear() !== $start_year) {
            return false;
        }
        if ($this->getSegment() !== $segment) {
            return false;
        }

        return true;
    }


}
