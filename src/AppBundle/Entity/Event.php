<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use AppBundle\Entity\Race;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventRepository")
 */
class Event
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="datetime")
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="datetime")
     */
    private $end;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_start", type="datetime")
     */
    private $registrationStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_end", type="datetime")
     */
    private $registrationEnd;

    /**
     * @var \Time
     *
     * @ORM\Column(name="representatives_meeting_start", type="datetime")
     */
    private $representativesMeetingStart;

    /**
     * @var \Time
     *
     * @ORM\Column(name="representatives_meeting_end", type="datetime")
     */
    private $representativesMeetingEnd;

    /**
     * @var string
     *
     * @ORM\Column(name="more_info_website", type="string", length=255, nullable=true)
     */
    private $moreInfoWebsite;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Race", mappedBy="event")
     */
    private $races;

    public function __construct()
    {
        $this->races = new ArrayCollection();
    }

    public function getRegistrationTimePercentage() {
        $now = new \DateTime();
        if ($now > $this->registrationEnd) {
            return 100;
        } elseif ($now < $this->registrationStart) {
            return -1;
        } else {
            $total_timespan = $this->registrationEnd->diff($this->registrationStart);
            $total = $total_timespan->i + $total_timespan->h * 60 + $total_timespan->d * 24 * 60;

            $rest_timespan = $now->diff($this->registrationEnd);
            $rest = $rest_timespan->i + $rest_timespan->h * 60 + $rest_timespan->d * 24 * 60;

            return round(100 * ($total - $rest) / $total);
        }
    }

    public function getRemainingRegistrationTime() {
        $format = '%d Tage und %h Stunden';
        $now = new \DateTime();
        $rest = $now->diff($this->registrationEnd)->format($format);
        return $rest;
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
     * Set name
     *
     * @param string $name
     *
     * @return Event
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
     * Set start
     *
     * @param \DateTime $start
     *
     * @return Event
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     *
     * @return Event
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set registrationStart
     *
     * @param \DateTime $registrationStart
     *
     * @return Event
     */
    public function setRegistrationStart($registrationStart)
    {
        $this->registrationStart = $registrationStart;

        return $this;
    }

    /**
     * Get registrationStart
     *
     * @return \DateTime
     */
    public function getRegistrationStart()
    {
        return $this->registrationStart;
    }

    /**
     * Set registrationEnd
     *
     * @param \DateTime $registrationEnd
     *
     * @return Event
     */
    public function setRegistrationEnd($registrationEnd)
    {
        $this->registrationEnd = $registrationEnd;

        return $this;
    }

    /**
     * Get registrationEnd
     *
     * @return \DateTime
     */
    public function getRegistrationEnd()
    {
        return $this->registrationEnd;
    }

    /**
     * Set representativesMeetingStart
     *
     * @param \Time $representativesMeetingStart
     *
     * @return Event
     */
    public function setRepresentativesMeetingStart($representativesMeetingStart)
    {
        $this->representativesMeetingStart = $representativesMeetingStart;

        return $this;
    }

    /**
     * Get representativesMeetingStart
     *
     * @return \Time
     */
    public function getRepresentativesMeetingStart()
    {
        return $this->representativesMeetingStart;
    }

    /**
     * Set representativesMeetingEnd
     *
     * @param \Time $representativesMeetingEnd
     *
     * @return Event
     */
    public function setRepresentativesMeetingEnd($representativesMeetingEnd)
    {
        $this->representativesMeetingEnd = $representativesMeetingEnd;

        return $this;
    }

    /**
     * Get representativesMeetingEnd
     *
     * @return \Time
     */
    public function getRepresentativesMeetingEnd()
    {
        return $this->representativesMeetingEnd;
    }

    /**
     * Set moreInfoWebsite
     *
     * @param string $moreInfoWebsite
     *
     * @return Event
     */
    public function setMoreInfoWebsite($moreInfoWebsite)
    {
        $this->moreInfoWebsite = $moreInfoWebsite;

        return $this;
    }

    /**
     * Get moreInfoWebsite
     *
     * @return string
     */
    public function getMoreInfoWebsite()
    {
        return $this->moreInfoWebsite;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get races
     *
     * @return ArrayCollection[Races]
     */
    public function getRaces() {
        return $this->races;
    }
}

