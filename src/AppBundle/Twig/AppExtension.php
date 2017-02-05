<?php

namespace AppBundle\Twig;

use AppBundle\Entity\Registration;

class AppExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('count', array($this, 'countFilter')),
            new \Twig_SimpleFilter('timeString', array($this, 'timeString')),
            new \Twig_SimpleFilter('sortByPlace', array($this, 'sortByPlace')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('count', array($this, 'countFilter')),
            new \Twig_SimpleFunction('sameDay', array($this, 'isSameDay')),
        );
    }

    public function countFilter($countable)
    {
        return count($countable);
    }

    public function timeString($seconds)
    {
        $t = $seconds;
        if ($t / floatval(3600) >= 1) {
            $hours = intval($t / floatval(3600));
            $t = $t - ($hours * floatval(3600));
        } else {
            $hours = 0;
        }

        if ($t / floatval(60) >= 1) {
            $minutes = intval($t / floatval(60));
            $t = $t - ($minutes * floatval(60));
        } else {
            $minutes = 0;
        }

        if ($t >= 1) {
            $seconds = intval($t);
            $t = $t - $seconds;
        } else {
            $seconds = 0;
        }

        if ($t > 0.0) {
            $fracSecs = intval(round($t * 10.0));
        } else {
            $fracSecs = 0;
        }

        if ($hours > 0) {
            $result = sprintf('%2d:%02d:%02d.%d', $hours, $minutes, $seconds, $fracSecs);
        } else {
            $result = sprintf('%2d:%02d.%d', $minutes, $seconds, $fracSecs);
        }

        return $result;
    }

    public function isSameDay(\DateTime $x, \DateTime $y) {
        $days = $x->diff($y, true)->days;
        return (0 == $days);
    }

    public function sortByPlace($registrations)
    {
        $result = array();
        // get all timings
        $timings = array();
        /** @var Registration $registration */
        foreach ($registrations as $registration) {
            $timings[$registration->getFinalTime()] = $registration;
        }
        ksort($timings);
        // replace key by place
        $i = 1;
        foreach ($timings as $x => $v) {
            $result[$i] = $v;
            $i++;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'app_extension';
    }
}