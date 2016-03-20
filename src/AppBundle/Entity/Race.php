<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use AppBundle\Entity\Event;

/**
 * Race
 *
 * @ORM\Table(name="race")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RaceRepository")
 */
class Race
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
     * @Assert\Type(type="AppBundle\Entity\Event")
     * @Assert\Valid()
     *
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="races")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    private $gender;

    /**
     * @var string
     *
     * @ORM\Column(name="age_class", type="string", length=255, nullable=true)
     */
    private $ageClass;

    /**
     * @var int
     *
     * @ORM\Column(name="age_min", type="smallint")
     */
    private $ageMin;

    /**
     * @var int
     *
     * @ORM\Column(name="age_max", type="smallint")
     */
    private $ageMax;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="smallint", nullable=true)
     */
    private $level;

    /**
     * @var int
     *
     * @ORM\Column(name="starter_min", type="smallint")
     */
    private $starterMin;

    /**
     * @var int
     *
     * @ORM\Column(name="starter_max", type="smallint")
     */
    private $starterMax;

    /**
     * @var string
     *
     * @ORM\Column(name="price_per_starter", type="decimal", precision=5, scale=2)
     */
    private $pricePerStarter;

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
     * Set gender
     *
     * @param string $gender
     *
     * @return Race
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

    /**
     * Set ageClass
     *
     * @param string $ageClass
     *
     * @return Race
     */
    public function setAgeClass($ageClass)
    {
        $this->ageClass = $ageClass;

        return $this;
    }

    /**
     * Get ageClass
     *
     * @return string
     */
    public function getAgeClass()
    {
        return $this->ageClass;
    }

    /**
     * Set ageMin
     *
     * @param integer $ageMin
     *
     * @return Race
     */
    public function setAgeMin($ageMin)
    {
        $this->ageMin = $ageMin;

        return $this;
    }

    /**
     * Get ageMin
     *
     * @return int
     */
    public function getAgeMin()
    {
        return $this->ageMin;
    }

    /**
     * Set ageMax
     *
     * @param integer $ageMax
     *
     * @return Race
     */
    public function setAgeMax($ageMax)
    {
        $this->ageMax = $ageMax;

        return $this;
    }

    /**
     * Get ageMax
     *
     * @return int
     */
    public function getAgeMax()
    {
        return $this->ageMax;
    }

    /**
     * Set level
     *
     * @param integer $level
     *
     * @return Race
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set starterMin
     *
     * @param integer $starterMin
     *
     * @return Race
     */
    public function setStarterMin($starterMin)
    {
        $this->starterMin = $starterMin;

        return $this;
    }

    /**
     * Get starterMin
     *
     * @return int
     */
    public function getStarterMin()
    {
        return $this->starterMin;
    }

    /**
     * Set starterMax
     *
     * @param integer $starterMax
     *
     * @return Race
     */
    public function setStarterMax($starterMax)
    {
        $this->starterMax = $starterMax;

        return $this;
    }

    /**
     * Get starterMax
     *
     * @return int
     */
    public function getStarterMax()
    {
        return $this->starterMax;
    }

    /**
     * Set pricePerStarter
     *
     * @param string $pricePerStarter
     *
     * @return Race
     */
    public function setPricePerStarter($pricePerStarter)
    {
        $this->pricePerStarter = $pricePerStarter;

        return $this;
    }

    /**
     * Get pricePerStarter
     *
     * @return string
     */
    public function getPricePerStarter()
    {
        return $this->pricePerStarter;
    }

    /**
     * Set event
     *
     * @param Event $event
     *
     * @return Race
     */
    public function setEvent($event) {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent() {
        return $this->event;
    }
}

