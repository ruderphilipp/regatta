<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Race;
use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;
use AppBundle\Entity\TeamPosition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Find the fastest teams of the overall event.
 */
class FastestController extends Controller
{
    /**
     * Lists the fastest teams.
     *
     * @Route("/event/{event}/fastest", name="fastest_index")
     * @Method("GET")
     */
    public function indexAction(Event $event)
    {
        $temp = array();
        /** @var Race $race */
        foreach ($event->getRaces() as $race) {
            // calculation of pace is only possible if there is a distance
            $distance = $race->getDistance();
            if (!is_null($distance) && 0 < $distance) {
                // get all competitors
                /** @var RaceSection $section */
                foreach ($race->getSections() as $section) {
                    if ($section->isFinished()) {
                        if (!array_key_exists($race->getRaceType(), $temp)) {
                            $temp[$race->getRaceType()] = array();
                        }
                        /** @var Registration $registration */
                        foreach ($section->getValidRegistrations() as $registration) {
                            // take only finished SINGLEs
                            if ($registration->isFinished() && 1 == $registration->getTeam()->getMembers()->count()) {
                                // calculate pace (time per 500m)
                                $pace = strval(floatval($registration->getFinalTime() / $distance * 500.0));
                                $temp[$race->getRaceType()][$pace][] = $registration;
                            }
                        }
                    }
                }
            }
        }
        // sort in each type by pace
        foreach ($temp as $key => $values) {
            $me = $values;
            ksort($me);
            $temp[$key] = $me;
        }

        // take only the top 15 male and female ones
        $males = array();
        $females = array();
        $max = 15;
        foreach ($temp as $key => $values) {
            $males[$key] = array();
            $females[$key] = array();
            $m_counter = 0;
            $f_counter = 0;
            foreach ($values as $pace => $registrations) {
                foreach ($registrations as $registration) {
                    $myGender = $this->getGender($registration);
                    if ('m' == $myGender && $m_counter < $max) {
                        if (!array_key_exists($pace, $males[$key])) {
                            $males[$key][$pace] = array();
                        }
                        $males[$key][$pace][] = $registration;
                        $m_counter++;
                    } elseif ('w' == $myGender && $f_counter < $max) {
                        if (!array_key_exists($pace, $females[$key])) {
                            $females[$key][$pace] = array();
                        }
                        $females[$key][$pace][] = $registration;
                        $f_counter++;
                    }
                    if ($f_counter == $max && $m_counter == $max) {
                        break;
                    }
                }
                if ($f_counter == $max && $m_counter == $max) {
                    break;
                }
            }
        }

        return $this->render(
            'fastest/index.html.twig',
            array(
                'males' => $males,
                'females' => $females,
                'event' => $event,
            )
        );
    }

    private function getGender(Registration $registration) {
        /** @var TeamPosition $position */
        $position = $registration->getTeam()->getMembers()->first();
        return $position->getMembership()->getPerson()->getGender();
    }
}
