<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="racing_group")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RacingGroupRepository")
 */
class RacingGroup
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
     * @var int
     *
     * @ORM\Column(name="drvId", type="integer", nullable=true)
     */
    private $drvId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var Club
     *
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="groups")
     */
    private $club;

    /**
     * @var ArrayCollection[RacingGroupMembership]
     *
     * @ORM\OneToMany(targetEntity="RacingGroupMembership", mappedBy="group")
     */
    private $members;

    /**
     * @var RacingGroupsPerSection
     *
     * @ORM\ManyToOne(targetEntity="RacingGroupsPerSection", inversedBy="groups")
     */
    private $section;

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
     * Set drvId
     *
     * @param integer $drvId
     *
     * @return RacingGroup
     */
    public function setDrvId($drvId)
    {
        $this->drvId = $drvId;

        return $this;
    }

    /**
     * Get drvId
     *
     * @return int
     */
    public function getDrvId()
    {
        return $this->drvId;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return RacingGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Club $club
     * @return RacingGroup
     */
    public function setClub(Club $club)
    {
        $this->club = $club;

        return $this;
    }

    /**
     * @return Club
     */
    public function getClub()
    {
        return $this->club;
    }

    /**
     * @return ArrayCollection[RacingGroupMembership]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param RacingGroupsPerSection $section
     * @return RacingGroup
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return RacingGroupsPerSection
     */
    public function getSection()
    {
        return $this->section;
    }
}

