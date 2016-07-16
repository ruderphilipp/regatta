<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * All paid billings (not open ones!)
 *
 * @ORM\Table(name="billing")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BillingRepository")
 */
class Billing
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
     * How many Euro were paid?
     * @var int
     *
     * @ORM\Column(name="euro", type="smallint")
     */
    private $euro;

    /**
     * How many fractional Euro (e.i. cents) were paid?
     * @var int
     *
     * @ORM\Column(name="cent", type="smallint")
     */
    private $cent;

    /**
     * When did the club pay this amount?
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetimetz")
     */
    private $time;

    /**
     * How did the club do the payment?
     * @var string
     *
     * @ORM\Column(name="paymentType", type="string", length=255)
     */
    private $paymentType;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="billings")
     */
    private $event;

    /**
     * @var Club
     *
     * @ORM\ManyToOne(targetEntity="Club", inversedBy="billings")
     */
    private $club;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $euro
     * @return Billing
     */
    public function setEuro($euro)
    {
        $this->euro = $euro;

        return $this;
    }

    /**
     * @return int
     */
    public function getEuro()
    {
        return $this->euro;
    }

    /**
     * @param integer $cent
     * @return Billing
     */
    public function setCent($cent)
    {
        $this->cent = $cent;

        return $this;
    }

    /**
     * @return int
     */
    public function getCent()
    {
        return $this->cent;
    }

    /**
     * @param \DateTime $time
     * @return Billing
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param string $paymentType
     * @return Billing
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     *s @return Club
     */
    public function getClub()
    {
        return $this->club;
    }

}