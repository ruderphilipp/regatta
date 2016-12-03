<?php

namespace AppBundle\Entity;

use AppBundle\Repository\RaceRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

use AppBundle\Entity\Event;

/**
 * Race
 *
 * @ORM\Table(name="races")
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
     * @var Event
     *
     * @Assert\Type(type="AppBundle\Entity\Event")
     * @Assert\Valid()
     *
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="races")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id")
     */
    private $event;

    /**
     * @var int
     *
     * @ORM\Column(name="number_in_event", type="smallint")
     */
    private $numberInEvent;

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
     * @var int
     *
     * @ORM\Column(name="starter_per_section", type="smallint")
     */
    private $maxStarterPerSection;

    /**
     * @var string
     *
     * @ORM\Column(name="extra_text", type="string", length=255, nullable=true)
     */
    private $extraText;

    /**
     * @var string
     *
     * @ORM\Column(name="price_per_starter", type="decimal", precision=5, scale=2)
     */
    private $pricePerStarter;

    /**
     * @var int
     *
     * @ORM\Column(name="starter_per_team", type="smallint")
     */
    private $teamsize;

    /**
     * @var string
     *
     * @ORM\Column(name="race_type", type="string", length=255, nullable=true)
     */
    private $raceType;

    const TYPE_ROW = "row";
    const TYPE_RUN = "run";

    /**
     * In a Row&Run event, first all indoor rowing events will take place.
     * Later there are 1..n running races. The results/timings of them will
     * be added to the result/timing of a competitor in the rowing race to
     * get an overall rating.
     *
     * This mapping allows to link those two types of races to allow an
     * automatic result calculation.
     *
     * @see Race::rowRaces
     *
     * @var Race
     *
     * @ORM\ManyToOne(targetEntity="Race", inversedBy="rowRaces")
     */
    private $runRace;

    /**
     * In a Row&Run event, first all indoor rowing events will take place.
     * Later there are 1..n running races. The results/timings of them will
     * be added to the result/timing of a competitor in the rowing race to
     * get an overall rating.
     *
     * This mapping allows to link those two types of races to allow an
     * automatic result calculation.
     *
     * @see Race::runRace
     *
     * @var ArrayCollection[Race]
     *
     * @ORM\OneToMany(targetEntity="Race", mappedBy="runRace")
     */
    private $rowRaces;

    /**
     * @var ArrayCollection[RaceSection]
     *
     * @ORM\OneToMany(targetEntity="RaceSection", mappedBy="race")
     */
    private $sections;

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
     * Set number of this race in the context of the inspected event
     *
     * @param int $number
     *
     * @return Race
     */
    public function setNumberInEvent($number)
    {
        $this->numberInEvent = $number;

        return $this;
    }

    /**
     * Get number of this race in the context of the inspected event
     *
     * @return int
     */
    public function getNumberInEvent()
    {
        return $this->numberInEvent;
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
     * Set maximum number of groups per section
     *
     * @param integer $maxStarterPerSection
     * @return Race
     */
    public function setMaxStarterPerSection($maxStarterPerSection)
    {
        $this->maxStarterPerSection = $maxStarterPerSection;

        return $this;
    }

    /**
     * Get maximum number of groups per section
     *
     * @return integer
     */
    public function getMaxStarterPerSection()
    {
        return $this->maxStarterPerSection;
    }

    /**
     * Get explanatory text for this race
     *
     * @return string
     */
    public function getExtraText()
    {
        return $this->extraText;
    }

    /**
     * Set explanatory text for this race
     *
     * @param string text
     *
     * @return Race
     */
    public function setExtraText($extraText)
    {
        $this->extraText = $extraText;

        return $this;
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
     * Set number of competitors per group/boat
     *
     * @param int $teamsize
     *
     * @return Race
     */
    public function setTeamsize($teamsize)
    {
        $this->teamsize = $teamsize;

        return $this;
    }

    /**
     * Get number of competitors per group/boat
     *
     * @return int
     */
    public function getTeamsize()
    {
        return $this->teamsize;
    }

    /**
     * Set event
     *
     * @param Event $event
     *
     * @return Race
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set race type
     *
     * @param string $type
     *
     * @return Race
     */
    public function setRaceType($type)
    {
        $this->raceType = $type;

        return $this;
    }

    /**
     * Get race type
     *
     * @return string
     */
    public function getRaceType()
    {
        return $this->raceType;
    }

    /**
     * Set run race
     *
     * @see Race::runRace
     *
     * @param Race $race
     *
     * @return Race
     * @throws \DomainException if not allowed
     */
    public function setRunRace(Race $race)
    {
        if (self::TYPE_ROW === $this->raceType && 1 == $this->getTeamsize()) {
            $this->runRace = $race;
            return $this;
        } else {
            throw new \DomainException("Only allowed for (indoor) rowing races with one person!");
        }
    }

    /**
     * Get run race
     *
     * @see Race::runRace
     *
     * @return Race|null
     */
    public function getRunRace()
    {
        return $this->runRace;
    }

    /**
     * Get all corresponding rowing races if this is a running race
     * @return ArrayCollection[Race]
     */
    public function getRowRaces()
    {
        return $this->rowRaces;
    }

    /**
     * Get all sections of this race
     * @return ArrayCollection[RaceSection]
     */
    public function getSections()
    {
        return $this->sections;
    }

    public function __toString() {
        return "#" . $this->numberInEvent . ": " . RaceRepository::getOfficialName($this);
    }
}

