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
        if ($rg->getSections()->getSection()->getRace() != $race) {
            $this->addFlash(
                'error',
                'Falsche Inputdaten!'
            );
        } else {
            // mark the "lane" of the section as cancelled
            $rg->getSections()->setDeregistered();
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'notice',
                'Mannschaft abgemeldet!'
            );
        }
        return $this->redirectToRoute('registration_edit', array(
            'event' => $race->getEvent()->getId(),
            'race' => $race->getId()));
    }
}