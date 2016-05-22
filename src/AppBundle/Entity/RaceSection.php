<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RaceSection
 *
 * @ORM\Table(name="race_section")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaceSectionRepository")
 */
class RaceSection
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Counter of sections in a particular race (first, second, ...)
     *
     * @var int
     *
     * @ORM\Column(name="number", type="integer")
     */
    private $number;

    /**
     * @var Race
     *
     * @ORM\ManyToOne(targetEntity="Race", inversedBy="sections")
     */
    private $race;

    /**
     * @var ArrayCollection[RacingGroupsPerSection]
     *
     * @ORM\OneToMany(targetEntity="RacingGroupsPerSection", mappedBy="section")
     */
    private $groups;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set number
     *
     * @param integer $number
     *
     * @return RaceSection
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set race
     *
     * @param Race $race
     *
     * @return RaceSection
     */
    public function setRace($race)
    {
        $this->race = $race;

        return $this;
    }

    /**
     * Get race
     *
     * @return Race
     */
    public function getRace()
    {
        return $this->race;
    }

    /**
     * Get groups
     *
     * @return ArrayCollection[RacingGroupsPerSection]
     */
    public function getGroups()
    {
        return $this->groups;
    }
}

