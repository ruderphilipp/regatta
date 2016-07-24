<?php

namespace AppBundle\Controller;

use AppBundle\Entity\RaceSection;
use AppBundle\Entity\Registration;
use AppBundle\Repository\RaceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Race;
use AppBundle\Entity\Event;

/**
 * Race controller.
 */
class RaceController extends Controller
{
    /**
     * Lists all Race entities.
     *
     * @Route("/event/{event}/races/{onlyThoseThatCanBeStarted}", name="race_index")
     * @Method("GET")
     */
    public function indexAction(Event $event, $onlyThoseThatCanBeStarted = false)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var RaceRepository $repo */
        $repo = $em->getRepository('AppBundle:Race');
        if ($onlyThoseThatCanBeStarted) {
            $races = $repo->getAllRacesThatHaveRegistrations($event->getId());
        } else {
            $races = $event->getRaces();
        }

        return $this->render('race/index.html.twig', array(
            'races' => $races,
            'event' => $event,
            'rr' => $repo,
            'filtered' => $onlyThoseThatCanBeStarted,
        ));
    }

    /**
     * Creates a new Race entity.
     *
     * @Route("/event/{id}/race/new", name="race_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request, Event $event)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Race');
        $number = $repo->getLastNumberForEvent($event) + 1;

        $race = new Race();
        $form = $this->createForm('AppBundle\Form\RaceType', $race, array(
            'number' => $number
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $race->setEvent($event);
            $em->persist($race);
            $em->flush();

            $this->addFlash(
                'notice',
                'Rennen wurde angelegt!'
            );

            return $this->redirectToRoute('race_index', array('event' => $race->getEvent()->getId()));
        }

        //exit(\Doctrine\Common\Util\Debug::dump($form));

        return $this->render('race/new.html.twig', array(
            'race' => $race,
            'event' => $event,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Race entity.
     *
     * @Route("/event/{event}/race/{race}", name="race_show")
     * @Method("GET")
     */
    public function showAction(Race $race, Event $event)
    {
        $deleteForm = $this->createDeleteForm($race);

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Race');

        $invalidRegistrations = null;
        foreach($race->getSections() as $section) {
            foreach($section->getRegistrations() as $registration) {
                if (!$registration->isValidForRace()) {
                    if (is_null($invalidRegistrations)) {
                        $invalidRegistrations = array();
                    }
                    $invalidRegistrations[] = $registration;
                }
            }
        }

        return $this->render('race/show.html.twig', array(
            'race' => $race,
            'rr' => $repo,
            'delete_form' => $deleteForm->createView(),
            'invalidRegistrations' => $invalidRegistrations,
        ));
    }

    /**
     * Displays a form to edit an existing Race entity.
     *
     * @Route("/event/{event}/race/{race}/edit", name="race_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Race $race, Event $event)
    {
        $deleteForm = $this->createDeleteForm($race);
        $editForm = $this->createForm('AppBundle\Form\RaceType', $race);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($race);
            $em->flush();

            $this->addFlash(
                'notice',
                'Rennen wurde geändert!'
            );

            return $this->redirectToRoute('race_index', array('event' => $race->getEvent()->getId()));
        }

        return $this->render('race/edit.html.twig', array(
            'race' => $race,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Race entity.
     *
     * @Route("/race/{id}", name="race_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Race $race)
    {
        $form = $this->createDeleteForm($race);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($race);
            $em->flush();

            $this->addFlash(
                'notice',
                'Rennen wurde gelöscht!'
            );
        }

        return $this->redirectToRoute('race_index', array('event' => $race->getEvent()->getId()));
    }

    /**
     * Creates a form to delete a Race entity.
     *
     * @param Race $race The Race entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Race $race)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('race_delete', array('id' => $race->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Add a new section to this race.
     *
     * @Route("/race/{race}/section/add", name="race_add_section")
     * @Method("POST")
     */
    public function addSectionAction(Race $race)
    {
        $max = 0;
        /** @var RaceSection $section */
        foreach ($race->getSections() as $section) {
            if ($section->getNumber() > $max) {
                $max = $section->getNumber();
            }
        }
        $nextNumber = $max + 1;

        $em = $this->getDoctrine()->getManager();
        /** @var RaceRepository $raceRepo */
        $raceRepo = $em->getRepository('AppBundle:Race');
        $raceRepo->createSection($race, $nextNumber, $this->get('logger'));

        $this->addFlash(
            'notice',
            'Neue Abteilung angelegt.'
        );

        return $this->redirectToRoute('race_show',
            array(
                'event' => $race->getEvent()->getId(),
                'race' => $race->getId(),
            )
        );
    }

    /**
     * Remove all sections without competitors from this race.
     *
     * @Route("/race/{race}/section/clean", name="race_clean_sections")
     * @Method("POST")
     */
    public function cleanSectionsAction(Race $race)
    {
        $sectionOne = null;
        foreach ($race->getSections() as $section) {
            if(1 == $section->getNumber()) {
                $sectionOne = $section;
                break;
            }
        }
        if (is_null($sectionOne)) {
            // TODO better error message
            die("no section with number 1 found for this race!");
        }

        $em = $this->getDoctrine()->getManager();
        /** @var RaceSection $section */
        foreach ($race->getSections() as $section) {
            if (0 == $section->getValidRegistrations()->count()) {
                if (1 != $section->getNumber()) {
                    // are there some non-valids in this section?
                    if (0 < $section->getRegistrations()->count()) {
                        // move them all to section 1
                        /** @var Registration $registration */
                        foreach ($section->getRegistrations() as $registration) {
                            $registration->setSection($sectionOne);
                        }
                    }
//                    } else {
//                        // check if another section exists with
//                        // if so, then move them there
//                        // delete section one
//                        // make the new one to number one
                    $em->remove($section);
                }
            }
        }
        $em->flush();
        $em->refresh($race);

        $this->addFlash(
            'notice',
            'Leere Abteilungen entfernt.'
        );

        return $this->redirectToRoute('race_show',
            array(
                'event' => $race->getEvent()->getId(),
                'race' => $race->getId(),
            )
        );
    }
}
