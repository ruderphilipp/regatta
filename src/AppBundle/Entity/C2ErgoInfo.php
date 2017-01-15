<?php

namespace AppBundle\Entity;

/**
 * Concept2 Ergometer Information for VRA
 *
 * Simple DAO to collect all information needed to construct an input file for the "Concept 2 Venue Racing Application".
 */
class C2ErgoInfo
{
    /**
     * @var int identifier for the team on the ergo (bib number)
     */
    protected $id;

    /**
     * Participant Name
     *
     * Name that is shown on the "Concept2 PM" ergometer monitor during WARMUP. This is approximately 12 characters
     * long, but it is also dependent on width (names with wider characters such as “W” and capital letters take up more
     * space, which will result in fewer characters displayed on the PM. Rower Name will be also displayed to the user
     * while rowing. While rowing, the name is a bit shorter, (about 11 characters; as few as 8 characters with wide
     * characters and CAPS). You can make this string long if you like (but please avoid punctuation, especially commas)
     * the PM will truncate as needed. Finally, this string is also shown on the TV's. You can make the string longer,
     * and add in class information like "Scott Hamilton 36 HW" although this does not set the class as it is for
     * display only.
     *
     * @var string
     */
    protected $name;

    /**
     * Separation class
     *
     * If there are multiple classes of competitors in a single race, use this to separate the groups from each other.
     * Competitors of one group (C2 calls this "class") only see race information of group members. Furthermore, on the
     * screen each group has boats with different colors.
     *
     * If you are running a single class race, this can be left blank.
     *
     * @var string
     */
    protected $class;

    /**
     * Data of a single lane for the VRA export file
     * @param int $id team identifier
     * @param string $name participant name shown on screen and PM
     * @param string $class competition group (optional)
     */
    public function __construct($id, $name, $class = '')
    {
        if (is_null($id) || !is_int($id) || $id < 0) {
            throw new \InvalidArgumentException('ID must be positive integer!');
        }
        if (is_null($name) || 0 == strlen(trim($name))) {
            throw new \InvalidArgumentException('Name must have at least one character!');
        }
        $this->id = $id;
        $this->name = $name;
        $this->class = (string)$class;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

}