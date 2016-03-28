<?php

namespace AppBundle\Twig;

class AppExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('count', array($this, 'countFilter')),
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

    public function isSameDay(\DateTime $x, \DateTime $y) {
        $days = $x->diff($y, true)->days;
        return (0 == $days);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'app_extension';
    }
}