<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Competitor
 *
 * @ORM\Table(name="competitors")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CompetitorRepository")
 */
class Competitor
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
     * @var string
     *
     * @ORM\Column(name="drv_id", type="string", length=12, nullable=true)
     */
    private $drvId;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255)
     */
    private $lastName;

    /**
     * @var int
     *
     * @ORM\Column(name="year_of_birth", type="smallint")
     */
    private $yearOfBirth;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    private $gender;

    /**
     * @ORM\OneToMany(targetEntity="Membership", mappedBy="person")
     */
    private $memberships;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
    }

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
     * Set id of a competitor at the German Rowing Association
     *
     * @param string $id
     *
     * @return Competitor
     */
    public function setDrvId($id)
    {
        $this->drvId = $id;

        return $this;
    }

    /**
     * Get id of a competitor at the German Rowing Association
     *
     * CAUTION: it is a string like DE-12345-6
     *
     * @return string
     */
    public function getDrvId()
    {
        return $this->drvId;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Competitor
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Competitor
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set yearOfBirth
     *
     * @param integer $yearOfBirth
     *
     * @return Competitor
     */
    public function setYearOfBirth($yearOfBirth)
    {
        $this->yearOfBirth = $yearOfBirth;

        return $this;
    }

    /**
     * Get yearOfBirth
     *
     * @return int
     */
    public function getYearOfBirth()
    {
        return $this->yearOfBirth;
    }

    /**
     * Get age of this person at the current date in years
     *
     * @return int age
     */
    public function getAge()
    {
        $now = (int)(new \DateTime())->format('Y');
        return $now - $this->getYearOfBirth();
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Competitor
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    public function getGenderSymbol()
    {
        $result = '';
        if ($this->gender == 'm') {
            $result = '♂';
        } elseif ($this->gender == 'w') {
            $result = '♀';
        }
        return $result;
    }

    public function __toString() {
        return $this->getLastName().', '.$this->getFirstName();
    }

    /**
     * Get memberships
     *
     * @return ArrayCollection[Membership]
     */
    public function getMemberships()
    {
        return $this->memberships;
    }
}

