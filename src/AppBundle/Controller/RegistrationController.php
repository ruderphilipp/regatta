<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Club;
use AppBundle\Entity\Event;
use AppBundle\Entity\Race;
use AppBundle\Entity\RacingGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class RegistrationController extends Controller
{
    /**
     * Show page to modify participation (re-register for different race or de-register from this race)
     *
     * @Route("/event/{event}/race/{race}/change", name="registration_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Event $event, Race $race)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Race');

        return $this->render('registration/edit.html.twig', array(
            'race' => $race,
            'rr' => $repo,
        ));
    }

    /**
     * Mark the given competitor group as being not any longer part of the given race.
     *
     * @Route("/race/{race}/deregister/{rg}", name="registration_delete")
     * @Method("POST")
     */
    public function deleteAction(RacingGroup $rg, Race $race)
    {
        // sanity check
        $races = array();
        /** @var \AppBundle\Entity\RacingGroupsPerSection $section */
        foreach($rg->getSections() as $section) {
            array_push($races, $section->getSection()->getRace());
        }
        if (!in_array($race, $races)) {
            $this->addFlash(
                'error',
                'Falsche Inputdaten!'
            );
        } else {
            /** @var \AppBundle\Entity\RacingGroupsPerSection $mySection */
            $mySection = null;
            // find the "lane"
            foreach($rg->getSections() as $section) {
                if ($section->getSection()->getRace() == $race) {
                    $mySection = $section;
                }
            }
            if (is_null($mySection)) {
                $this->addFlash(
                    'error',
                    'Falsche Inputdaten! Konnte Startbahn nicht ermitteln...'
                );
            } else {
                // mark the "lane" of the section as cancelled
                $mySection->setDeregistered();
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash(
                    'notice',
                    'Mannschaft abgemeldet!'
                );
            }
        }
        return $this->redirectToRoute('registration_edit', array(
            'event' => $race->getEvent()->getId(),
            'race' => $race->getId()));
    }
}