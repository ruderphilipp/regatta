<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Race;
use AppBundle\Entity\Registration;
use AppBundle\Entity\Event;
use AppBundle\Repository\RaceRepository;
use AppBundle\Repository\TeamRepository;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Race start controller.
 */
class StartController extends Controller
{
    /**
     * Overview for starting a race
     *
     * @Route("/event/{event}/race/{race}/start", name="race_start")
     * @Method("GET")
     * @Security("has_role('ROLE_REGISTRATION') or has_role('ROLE_REFEREE')")
     */
    public function showAction(Event $event, Race $race)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');

        // check if enough competitors (do not trust GUI logic to not call the method/link)
        if ($repo->getNumberOfRegistrations($race) < $race->getStarterMin()) {
            $this->addFlash(
                'error',
                'Mindestteilnehmeranzahl nicht erreicht!'
            );

            return $this->redirectToRoute('race_show', array('event' => $event->getId(), 'race' => $race->getId()));
        }

        return $this->render('race/start.html.twig', array(
            'race' => $race,
            'race_name' => $repo->getOfficialName($race),
        ));
    }

    /**
     * Show overview of all startable races to start multiple at once.
     *
     * @Route("/event/{event}/start", name="race_start_all")
     * @Method("GET")
     * @Security("has_role('ROLE_REFEREE')")
     */
    public function showAllAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');
        $allRaces = $repo->getAllRacesThatHaveRegistrations($event->getId());
        $races = array();
        /** @var Race $race */
        foreach ($allRaces as $race) {
            if (!$repo->isFinished($race)) {
                $races[] = $race;
            }
        }
        if (0 == count($races)) {
            $this->addFlash(
                'error',
                'Keine startbaren Rennen gefunden!'
            );
            $this->redirect($request->headers->get('referer'));
        }

        return $this->render('race/startAll.html.twig', array(
            'races' => $races,
            'event' => $event,
            'rr' => $repo,
        ));
    }

    /**
     * Mark a team (registration) as not at start to be able to get an overview
     * and begin the race start sequence if all other competitors are at start.
     *
     * @Route("/team/{registration}/NotAtStart", name="race_start_nas")
     * @Method("GET")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function notAtStartAction(Request $request, Registration $registration)
    {
        $em = $this->getDoctrine()->getManager();
        $registration->setNotAtStart();
        $em->persist($registration);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Reset a team (registration) after marking it as "not at start", so that a new registration is possible.
     *
     * @see notAtStartAction
     *
     * @Route("/team/{registration}/resetNas", name="race_start_reset")
     * @Method("GET")
     * @Security("has_role('ROLE_REGISTRATION')")
     */
    public function resetNotAtStart(Request $request, Registration $registration)
    {
        $em = $this->getDoctrine()->getManager();
        $registration->undoNotAtStart();
        $em->persist($registration);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}
