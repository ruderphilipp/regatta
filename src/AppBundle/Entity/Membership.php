<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * Membership
 *
 * @ORM\Table(name="memberships")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MembershipRepository")
 */
class Membership
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
     * @var \DateTime
     *
     * @ORM\Column(name="since", type="date")
     */
    private $since;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="until", type="date", nullable=true)
     */
    private $until;

    /**
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="memberships")
     * @ORM\JoinColumn(name="club_id", referencedColumnName="id")
     */
    private $club;

    /**
     * @ORM\ManyToOne(targetEntity="Competitor", inversedBy="memberships")
     * @ORM\JoinColumn(name="competitor_id", referencedColumnName="id")
     */
    private $person;


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
     * Set since
     *
     * @param \DateTime $since
     *
     * @return Membership
     */
    public function setSince($since)
    {
        $this->since = $since;

        return $this;
    }

    /**
     * Get since
     *
     * @return \DateTime
     */
    public function getSince()
    {
        return $this->since;
    }

    /**
     * Set until
     *
     * @param \DateTime $until
     *
     * @return Membership
     */
    public function setUntil($until)
    {
        $this->until = $until;

        return $this;
    }

    /**
     * Get until
     *
     * @return \DateTime
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * Set until
     *
     * @param Club $club
     *
     * @return Membership
     */
    public function setClub(Club $club)
    {
        $this->club = $club;

        return $this;
    }

    /**
     * Get club
     *
     * @return Club
     */
    public function getClub()
    {
        return $this->club;
    }

    /**
     * Set Person
     *
     * @param Competitor $person
     *
     * @return Membership
     */
    public function setPerson($person)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return Competitor
     */
    public function getPerson()
    {
        return $this->person;
    }

    public function isValidAt(\DateTime $date)
    {
        return ($this->since < $date && (null == $this->until || $date < $this->until));
    }

}

