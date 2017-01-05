<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Club;
use AppBundle\Entity\Event;
use AppBundle\Entity\Race;
use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;
use AppBundle\Repository\BillingRepository;
use AppBundle\Repository\RaceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class BillingController extends Controller
{
    /**
     * Lists all clubs that need to pay in this event.
     *
     * @Route("/event/{event}/billing", name="billing_index")
     * @Method("GET")
     */
    public function indexAction(Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var RaceRepository $raceRepo */
        $raceRepo = $em->getRepository('AppBundle:Race');
        $races = $raceRepo->getAllRacesThatHaveRegistrations($event->getId());
        // create a list of all clubs
        $clubs = array();
        /** @var Race $race */
        foreach ($races as $race) {
            /** @var RaceSection $section */
            foreach($race->getSections() as $section) {
                /** @var Registration $registration */
                foreach($section->getRegistrations() as $registration) {
                    $club = $registration->getTeam()->getClub();
                    if (!in_array($club, $clubs)) {
                        $clubs[] = $club;
                    }
                }
            }
        }
        /** @var BillingRepository $billingRepo */
        $billingRepo = $em->getRepository('AppBundle:Billing');

        return $this->render('billing/index.html.twig', array(
            'clubs' => $clubs,
            'event' => $event,
            'repo' => $billingRepo,
        ));
    }

    /**
     * Lists all races and payment amounts for a specific club.
     *
     * @Route("/event/{event}/billing/{club}", name="billing_show")
     * @Method("GET")
     */
    public function showAction(Event $event, Club $club)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var RaceRepository $raceRepo */
        $raceRepo = $em->getRepository('AppBundle:Race');
        // $paid = $repo->findBy(array('event' => $event->getId(), 'club' => $club->getId()));

        $races = $raceRepo->getAllRacesThatHaveRegistrations($event->getId());
        $billingPositions = array();
        $total = 0.0;

        /** @var Race $race */
        foreach ($races as $race) {
            $priceString = $race->getPricePerStarter();
            $price = floatval(preg_replace("/[^0-9.]/", "", preg_replace("/,/", ".", $priceString)));
            /** @var RaceSection $section */
            foreach($race->getSections() as $section) {
                /** @var Registration $registration */
                foreach($section->getRegistrations() as $registration) {
                    /** @var Club $myClub */
                    $myClub = $registration->getTeam()->getClub();
                    if ($club->getId() == $myClub->getId()) {
                        // TODO handle de-registered and those from other races
                        if (array_key_exists($race->getNumberInEvent(), $billingPositions)) {
                            $billingPositions[$race->getNumberInEvent()]['teams'] += 1;
                            $billingPositions[$race->getNumberInEvent()]['amount'] += $price;
                        } else {
                            $billingPositions[$race->getNumberInEvent()] = array(
                                'teams' => 1,
                                'amount' => $price,
                                'race' => $race
                            );
                        }
                        $total += $price;
                    }
                }
            }
        }
        // order by race number
        ksort($billingPositions);

        return $this->render('billing/show.html.twig', array(
            'club' => $club,
            'event' => $event,
            'positions' => $billingPositions,
            'rr' => $raceRepo,
            'total' => $total,
        ));
    }
}