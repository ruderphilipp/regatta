<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="teams")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TeamRepository")
 */
class Team
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
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="teams")
     */
    private $club;

    /**
     * @var ArrayCollection[TeamPosition]
     *
     * @ORM\OneToMany(targetEntity="TeamPosition", mappedBy="team")
     */
    private $members;

    /**
     * @var Registration
     *
     * @ORM\OneToMany(targetEntity="Registration", mappedBy="team")
     */
    private $registrations;

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
     * @return Team
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
     * @return Team
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
     * @return Team
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
     * @return ArrayCollection[TeamPosition]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @return ArrayCollection[Registration]
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }
}

