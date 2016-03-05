<?php

namespace AppBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Race;
use AppBundle\Entity\Event;
use AppBundle\Form\RaceType;

/**
 * Race controller.
 */
class RaceController extends Controller
{
    /**
     * Lists all Race entities.
     *
     * @Route("/event/{id}/races", name="race_index")
     * @Method("GET")
     */
    public function indexAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('AppBundle:Race');
        $races = $repo->findAllForEvent($id);

        return $this->render('race/index.html.twig', array(
            'races' => $races,
            'id' => $id,
            'rr' => $repo,
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
        $race = new Race();

        $form = $this->createForm('AppBundle\Form\RaceType', $race);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $race->setEvent($event);
            $em->persist($race);
            $em->flush();

            return $this->redirectToRoute('race_show', array('race' => $race->getId(), 'event' => $event->getId()));
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

        return $this->render('race/show.html.twig', array(
            'race' => $race,
            'delete_form' => $deleteForm->createView(),
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

            return $this->redirectToRoute('race_index', array('id' => $race->getEvent()->getId()));
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
        }

        return $this->redirectToRoute('race_index');
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
}
