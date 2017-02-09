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
     * @var float
     *
     * @ORM\Column(name="weight_max", type="decimal", precision=4, scale=1, nullable=true) // means 123.4 (4 total, 1 after dot)
     */
    private $weightMax;

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
     * Race distance in meters
     *
     * Usually a race is a single competition over fixed distance. Exceptions
     * are races that consist of multiple sections (e.g. a triathlon).
     *
     * @see Race::raceType
     *
     * @var int
     *
     * @ORM\Column(name="distance", type="integer", nullable=true)
     */
    private $distance;

    /**
     * Sometimes it is necessary to *not* have only one winning team for the
     * complete race but each per section (e.g. in junior races).
     *
     * @var boolean
     *
     * @ORM\Column(name="winner_per_section", type="boolean", options={"default" : false})
     */
    private $winnerPerSection;

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
     * Set weightMax
     *
     * @param float|null $weightMax
     *
     * @return Race
     */
    public function setWeightMax($weightMax)
    {
        if (is_null($weightMax) || (is_numeric($weightMax) && $weightMax == 0)) {
            $this->weightMax = null;
        } elseif (is_numeric($weightMax) && $weightMax > 0) {
            $this->weightMax = round($weightMax, 1); // round 1 after comma
        } else {
            throw new \InvalidArgumentException("Parameter must be either NULL or positive decimal!");
        }

        return $this;
    }

    /**
     * Get weightMax
     *
     * @return float|null
     */
    public function getWeightMax()
    {
        return $this->weightMax;
    }

    /**
     * Check if this race is a light weight race.
     *
     * @return bool
     */
    public function hasWeightLimit()
    {
        return !is_null($this->weightMax);
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
     * Set race distance
     *
     * @param int $distance
     *
     * @return Race
     */
    public function setDistance($distance)
    {
        if (is_null($distance) || (is_int($distance) && $distance > 0)) {
            $this->distance = $distance;
        } else {
            throw new \InvalidArgumentException("Distance must be a positive integer or null!");
        }

        return $this;
    }

    /**
     * Get race distance in meters
     *
     * @return int
     */
    public function getDistance()
    {
        return $this->distance;
    }

    /**
     * Set if each result should have a winner (or only the race in total, if false).
     *
     * @param boolean $bool The new value
     *
     * @return Race
     */
    public function setWinnerPerSection($bool)
    {
        if (is_bool($bool)) {
            $this->winnerPerSection = $bool;
        } else {
            throw new \InvalidArgumentException("ResultPerSection must be a boolean value!");
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasWinnerPerSection()
    {
        return $this->winnerPerSection;
    }

    /**
     * @return null|array[Registration] The overall winner of this particular race.
     * @throws \InvalidArgumentException if mode is not 'winner per total race'
     */
    public function getWinner()
    {
        if ($this->hasWinnerPerSection()) {
            throw new \InvalidArgumentException("Only allowed if mode 'winner per total race'!");
        }
        $conciderRunRace = false;
        if ($this->getEvent()->isRowAndRun() && !is_null($this->getRunRace())) {
            $conciderRunRace = true;
        }

        /** @var Registration $result */
        $result = null;
        foreach ($this->getSections() as $section) {
            /** @var Registration $secWinner */
            $secWinner = $section->getWinner($conciderRunRace);
            if (is_null($result)) {
                $result = $secWinner;
            } else {
                if ($secWinner->getFinalTime() < $result->getFinalTime()) {
                    $result = $secWinner;
                }
            }
        }
        return $result;
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

    /**
     * Get the first section in this race that is valid to add at least one more team.
     *
     * @return RaceSection The next valid race section where there is still space for new teams
     * @throws \Exception if there is no free section at all
     */
    public function tryToGetNextFreeSection()
    {
        $result = null;
        /** @var RaceSection $section */
        foreach ($this->getSections() as $section) {
            try {
                $section->tryToGetFirstFreeLane();
                // this will only be reached if there is a free lane
                $result = $section;
                break; // found one --> leave iteration
            } catch (\InvalidArgumentException $e) {
                // no free lane
            }
        }

        if (is_null($result)) {
            throw new \Exception("Konnte keine einzige freie Abteilung in dem Rennen ermitteln!");
        }

        return $result;
    }

    public function getNumberOfRegistrations() {
        $result = 0;
        /** @var RaceSection $section */
        foreach($this->getSections() as $section) {
            $result += $section->getValidRegistrations()->count();
        }
        return $result;
    }

    public function __toString() {
        return "#" . $this->numberInEvent . ": " . RaceRepository::getOfficialName($this);
    }
}

